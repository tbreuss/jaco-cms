<?php declare(strict_types=1);

namespace jaco\core\auth;

const TOKEN_AUDIENCE = 'ch.tebe.jaco';
const TOKEN_ISSUER = 'ch.tebe.jaco';
const TOKEN_TTL = 86400; // tokens are valid for 24 hours
const TOKEN_ALGORITHM = 'HS256';

// login throttle: three failures from one address within ten minutes block that address
// for ten minutes. The bucket is the address, not the username, so rotating names does
// not help; there is no permanent block, so nobody can lock the owner out. The address
// map is capped, so a distributed attack cannot grow the history file.
const LOGIN_MAX_ATTEMPTS = 3;
const LOGIN_BLOCK_SECONDS = 600;
const LOGIN_MAX_ADDRESSES = 1000;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use jaco\core\config\Config;

function authenticate(Config $config, string $username, string $password): ?string
{
    // fail closed: required security values may not be empty, no matter how the
    // Config was constructed. Without this, empty input would match an empty
    // username via hash_equals, and tokens would be signed with an empty key.
    // (An empty totpSecret is the exception: it means "no second factor".)
    // The password is stored as a password_hash() digest; password_verify()
    // rejects an empty or invalid digest by itself, so no explicit check here.
    if ($config->username === '' || $config->secretKey === '') {
        return null;
    }

    // both comparisons are constant time. The password is stored as a
    // password_hash() digest: password_verify() is constant time itself, and it
    // rejects a plaintext setting because that is not a valid hash.
    $isUsernameValid = \hash_equals($config->username, $username);
    $isPasswordValid = \password_verify($password, $config->password);

    if (!$isUsernameValid || !$isPasswordValid) {
        return null;
    }

    return create_token($config, $username);
}

function is_authenticated(Config $config, string $token): bool
{
    try {
        $payload = JWT::decode($token, new Key($config->secretKey, TOKEN_ALGORITHM));
    } catch (\Exception) {
        return false;
    }

    // read optional claims safely to avoid warnings for missing properties
    if (($payload->iss ?? null) !== TOKEN_ISSUER || ($payload->aud ?? null) !== TOKEN_AUDIENCE) {
        return false;
    }
    if (($payload->user ?? null) !== $config->username) {
        return false;
    }
    if (($payload->admin ?? null) !== true) {
        return false;
    }

    return true;
}

function create_token(Config $config, string $username): string
{
    $now = time();

    $payload = [
        'iss' => TOKEN_ISSUER,
        'aud' => TOKEN_AUDIENCE,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + TOKEN_TTL,
        'user' => $username,
        'admin' => true,
    ];

    return JWT::encode($payload, $config->secretKey, TOKEN_ALGORITHM);
}

/**
 * Seconds until this address may try again, 0 when it may.
 *
 * @return int<0, max>
 */
function get_login_block_remaining(Config $config, string $address): int
{
    // a command line caller has no address and is not throttled: whoever runs the
    // command already has file access
    if ($address === '') {
        return 0;
    }

    $window = recent_failures(read_login_history($config), $address);

    if (count($window) < LOGIN_MAX_ATTEMPTS) {
        return 0;
    }

    // every entry in the window is newer than time() - LOGIN_BLOCK_SECONDS,
    // so the remaining seconds cannot drop below zero
    return max(max($window) + LOGIN_BLOCK_SECONDS - time(), 0);
}

function record_login_failure(Config $config, string $address): void
{
    if ($address === '') {
        return;
    }

    $history = read_login_history($config);
    $fails = recent_failures($history, $address);
    $fails[] = time();
    // only the count within the window and the latest timestamp matter
    $history['addresses'][$address] = array_slice($fails, -LOGIN_MAX_ATTEMPTS);

    write_login_history($config, $history);
}

function record_login_success(Config $config, string $address): void
{
    if ($address === '') {
        return;
    }

    $history = read_login_history($config);

    if (!isset($history['addresses'][$address])) {
        return;
    }

    unset($history['addresses'][$address]);

    write_login_history($config, $history);
}

function clear_login_history(Config $config): void
{
    write_login_history($config, ['addresses' => []]);
}

/**
 * @param array{addresses: array<string, list<int>>} $history
 * @return list<int> timestamps of this address within the block window
 */
function recent_failures(array $history, string $address): array
{
    $window = array_filter(
        $history['addresses'][$address] ?? [],
        fn(int $timestamp): bool => $timestamp > time() - LOGIN_BLOCK_SECONDS,
    );

    return array_slice(array_values($window), -LOGIN_MAX_ATTEMPTS);
}

/** @return array{addresses: array<string, list<int>>} */
function read_login_history(Config $config): array
{
    $content = @file_get_contents($config->loginHistoryFile);

    if (!is_string($content)) {
        return ['addresses' => []];
    }

    $decoded = json_decode($content, true);

    if (!is_array($decoded) || !is_array($decoded['addresses'] ?? null)) {
        return ['addresses' => []];
    }

    // the file content is untrusted, coerce it into shape
    $addresses = [];
    foreach ($decoded['addresses'] as $address => $times) {
        if (!is_string($address) || !is_array($times)) {
            continue;
        }
        $clean = array_values(array_filter($times, 'is_int'));
        if (count($clean) > 0) {
            $addresses[$address] = $clean;
        }
    }

    return ['addresses' => $addresses];
}

/**
 * Keeps only what can still block: entries outside the window are dropped and the number
 * of addresses is capped, so an address that stopped failing loses its entry and a
 * distributed attack cannot grow the file without bound.
 *
 * @param array{addresses: array<string, list<int>>} $history
 * @return array{addresses: array<string, list<int>>}
 */
function prune_login_history(array $history): array
{
    $kept = [];

    foreach ($history['addresses'] as $address => $times) {
        $window = array_values(array_filter(
            $times,
            fn(int $timestamp): bool => $timestamp > time() - LOGIN_BLOCK_SECONDS,
        ));
        if (count($window) > 0) {
            $kept[$address] = $window;
        }
    }

    if (count($kept) > LOGIN_MAX_ADDRESSES) {
        // newest first: the most recently active addresses survive
        uasort($kept, fn(array $a, array $b): int => max($b) <=> max($a));
        $kept = array_slice($kept, 0, LOGIN_MAX_ADDRESSES, true);
    }

    return ['addresses' => $kept];
}

/** @param array{addresses: array<string, list<int>>} $history */
function write_login_history(Config $config, array $history): void
{
    $history = prune_login_history($history);

    $dir = \dirname($config->loginHistoryFile);

    if (!\is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $encoded = json_encode($history);

    if ($encoded === false || @file_put_contents($config->loginHistoryFile, $encoded, LOCK_EX) === false) {
        error_log('Could not write login history to ' . $config->loginHistoryFile);
    }
}

<?php declare(strict_types=1);

namespace jaco\core\auth;

const TOKEN_AUDIENCE = 'ch.tebe.jaco';
const TOKEN_ISSUER = 'ch.tebe.jaco';
const TOKEN_TTL = 86400; // tokens are valid for 24 hours
const TOKEN_ALGORITHM = 'HS256';

// roles, lowest privilege first. A route names the role it needs, role_allows()
// compares the rank, and an unknown role in settings.php falls back to viewer.
const ROLE_VIEWER = 'viewer';
const ROLE_EDITOR = 'editor';
const ROLE_ADMIN = 'admin';
const ROLE_RANK = [ROLE_VIEWER => 1, ROLE_EDITOR => 2, ROLE_ADMIN => 3];

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
    // fail closed: without a secret there is nothing to sign, and without users
    // nobody can log in. (An empty totpSecret is the exception: no second factor.)
    // The password is stored as a password_hash() digest; password_verify() rejects
    // an empty or invalid digest by itself, so no explicit check here.
    if ($config->secretKey === '') {
        return null;
    }

    $user = find_user($config, $username);

    // constant time, and an unknown user takes the same path as a wrong password
    if ($user === null || !\password_verify($password, $user['password'])) {
        return null;
    }

    return create_token($config, $username, $user['role']);
}

/**
 * Checks a password against the stored digest of that user. Constant time, and an
 * unknown user takes the same path as a wrong password.
 */
function verify_password(Config $config, string $username, string $password): bool
{
    $user = find_user($config, $username);

    return $user !== null && \password_verify($password, $user['password']);
}

/**
 * The user with this name, or null. The users list in settings.php is the only source;
 * there is no separate username/password pair any more.
 *
 * @return array{password: string, role: string}|null
 */
function find_user(Config $config, string $username): ?array
{
    $user = $config->users[$username] ?? null;

    if (!is_array($user) || !is_string($user['password'] ?? null) || $user['password'] === '') {
        return null;
    }

    $role = $user['role'] ?? '';

    return [
        'password' => $user['password'],
        // fail closed: an unknown role gets the least privilege
        'role' => is_string($role) && isset(ROLE_RANK[$role]) ? $role : ROLE_VIEWER,
    ];
}

function role_allows(string $actual, string $required): bool
{
    return (ROLE_RANK[$actual] ?? 0) >= (ROLE_RANK[$required] ?? PHP_INT_MAX);
}

/**
 * Claims of a valid token, or null. The role comes from the signed payload; an
 * unknown claim falls back to viewer, and a token whose user no longer exists is
 * rejected (removing a user ends their sessions).
 *
 * @return array{user: string, role: string}|null
 */
function read_token(Config $config, string $token): ?array
{
    try {
        $payload = JWT::decode($token, new Key($config->secretKey, TOKEN_ALGORITHM));
    } catch (\Exception) {
        return null;
    }

    // read optional claims safely to avoid warnings for missing properties
    if (($payload->iss ?? null) !== TOKEN_ISSUER || ($payload->aud ?? null) !== TOKEN_AUDIENCE) {
        return null;
    }

    $user = (string) ($payload->user ?? '');

    if (find_user($config, $user) === null) {
        return null;
    }

    $role = (string) ($payload->role ?? '');

    return ['user' => $user, 'role' => isset(ROLE_RANK[$role]) ? $role : ROLE_VIEWER];
}

function is_authenticated(Config $config, string $token): bool
{
    return read_token($config, $token) !== null;
}

function create_token(Config $config, string $username, string $role): string
{
    $now = time();

    $payload = [
        'iss' => TOKEN_ISSUER,
        'aud' => TOKEN_AUDIENCE,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + TOKEN_TTL,
        'user' => $username,
        'role' => $role,
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
    if (!is_file($config->loginHistoryFile)) {
        return ['addresses' => []];
    }

    $content = file_get_contents($config->loginHistoryFile);

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
        mkdir($dir, 0755, true);
    }

    $encoded = json_encode($history);

    if ($encoded === false || file_put_contents($config->loginHistoryFile, $encoded, LOCK_EX) === false) {
        error_log('Could not write login history to ' . $config->loginHistoryFile);
    }
}

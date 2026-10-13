<?php declare(strict_types=1);

namespace jaco\core\auth;

const TOKEN_AUDIENCE = 'ch.tebe.jaco';
const TOKEN_ISSUER = 'ch.tebe.jaco';
const TOKEN_TTL = 86400; // tokens are valid for 24 hours
const TOKEN_ALGORITHM = 'HS256';

// login throttle: 3 fails within 10 minutes block the login for 10 minutes,
// 15 fails in total block it permanently until the history file is deleted
const LOGIN_MAX_ATTEMPTS = 3;
const LOGIN_BLOCK_SECONDS = 600;
const LOGIN_MAX_TOTAL = 15;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use jaco\core\config\Config;

function authenticate(Config $config, string $username, string $password): ?string
{
    // compare both values in constant time to prevent timing attacks
    $isUsernameValid = \hash_equals($config->username, $username);
    $isPasswordValid = \hash_equals($config->password, $password);

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
 * Seconds until the login is allowed again, 0 when not blocked.
 * The throttle counts every failed attempt in one global bucket:
 * rotating usernames neither avoids the block nor reveals whether
 * a name exists, since the response never depends on the name.
 * A permanent block never expires and must be lifted by deleting
 * the login history file.
 *
 * @return int<0, max>
 */
function get_login_block_remaining(Config $config): int
{
    $history = read_login_history($config);

    if ($history['total'] >= LOGIN_MAX_TOTAL) {
        return PHP_INT_MAX;
    }

    $window = array_filter(
        $history['fails'],
        fn(int $timestamp): bool => $timestamp > time() - LOGIN_BLOCK_SECONDS,
    );

    if (count($window) < LOGIN_MAX_ATTEMPTS) {
        return 0;
    }

    // every entry in the window is newer than time() - LOGIN_BLOCK_SECONDS,
    // so the remaining seconds cannot drop below zero
    return max(max($window) + LOGIN_BLOCK_SECONDS - time(), 0);
}

function record_login_failure(Config $config): void
{
    $history = read_login_history($config);
    // prune expired entries and cap the list: only the count within the
    // window and the latest timestamp matter, so sustained brute force
    // cannot grow the file beyond a few entries
    $history['fails'] = array_values(array_filter(
        $history['fails'],
        fn(int $timestamp): bool => $timestamp > time() - LOGIN_BLOCK_SECONDS,
    ));
    $history['fails'][] = time();
    $history['fails'] = array_slice($history['fails'], -LOGIN_MAX_ATTEMPTS);
    $history['total'] = $history['total'] + 1;

    write_login_history($config, $history);
}

function record_login_success(Config $config): void
{
    if (read_login_history($config) !== ['fails' => [], 'total' => 0]) {
        write_login_history($config, ['fails' => [], 'total' => 0]);
    }
}

function clear_login_history(Config $config): void
{
    record_login_success($config);
}

/** @return array{fails: list<int>, total: int} */
function read_login_history(Config $config): array
{
    $content = @file_get_contents($config->loginHistoryFile);

    if (!is_string($content)) {
        return ['fails' => [], 'total' => 0];
    }

    $decoded = json_decode($content, true);

    if (!is_array($decoded)) {
        return ['fails' => [], 'total' => 0];
    }

    // the file content is untrusted, coerce it into shape
    $fails = array_values(array_filter($decoded['fails'] ?? [], 'is_int'));
    $total = $decoded['total'] ?? 0;

    return ['fails' => $fails, 'total' => is_int($total) ? $total : 0];
}

/** @param array{fails: list<int>, total: int} $history */
function write_login_history(Config $config, array $history): void
{
    $dir = \dirname($config->loginHistoryFile);

    if (!\is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $encoded = json_encode($history);

    if ($encoded === false || @file_put_contents($config->loginHistoryFile, $encoded, LOCK_EX) === false) {
        error_log('Could not write login history to ' . $config->loginHistoryFile);
    }
}

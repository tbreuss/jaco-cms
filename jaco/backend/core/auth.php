<?php declare(strict_types=1);

namespace jaco\core\auth;

const AUDIENCE = 'ch.tebe.jaco';
const ISSUER = 'ch.tebe.jaco';
const TOKEN_TTL = 86400; // tokens are valid for 24 hours

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use const jaco\PASSWORD;
use const jaco\SECRET_KEY;
use const jaco\USERNAME;

function authenticate(string $username, string $password): ?string
{
    // compare both values in constant time to prevent timing attacks
    $isUsernameValid = \hash_equals(USERNAME, $username);
    $isPasswordValid = \hash_equals(PASSWORD, $password);

    if (!$isUsernameValid || !$isPasswordValid) {
        return null;
    }

    return create_token($username);
}

function is_authenticated(string $token): bool
{
    try {
        $payload = JWT::decode($token, new Key(SECRET_KEY, 'HS512'));
    } catch (\Exception) {
        return false;
    }

    // read optional claims safely to avoid warnings for missing properties
    if (($payload->iss ?? null) !== ISSUER || ($payload->aud ?? null) !== AUDIENCE) {
        return false;
    }
    if (($payload->user ?? null) !== USERNAME) {
        return false;
    }
    if (($payload->admin ?? null) !== true) {
        return false;
    }

    return true;
}

function create_token(string $username): string
{
    $now = time();

    $payload = [
        'iss' => ISSUER,
        'aud' => AUDIENCE,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + TOKEN_TTL,
        'user' => $username,
        'admin' => true,
    ];

    return JWT::encode($payload, SECRET_KEY, 'HS512');
}

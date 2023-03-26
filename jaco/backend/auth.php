<?php

declare(strict_types=1);

namespace jaco\auth;

const AUDIENCE = 'com.jaco';
const ISSUER = 'com.jaco';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use const jaco\PASSWORD;
use const jaco\SECRET_KEY;
use const jaco\USERNAME;

function authenticate(string $username, string $password): ?string
{
    if ($username !== USERNAME || $password !== PASSWORD) {
        return null;
    }

    $payload = [
        'iss' => ISSUER,
        'aud' => AUDIENCE,
        'iat' => time(),
        'nbf' => time(),
    ];

    return JWT::encode($payload, SECRET_KEY, 'HS512');
}

function is_authenticated(string $token): bool
{
    try {
        $payload = JWT::decode($token, new Key(SECRET_KEY, 'HS512'));
    } catch (\Exception) {
        return false;
    }
    return ($payload->iss === ISSUER)
        && ($payload->aud === AUDIENCE);
}

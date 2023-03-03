<?php

declare(strict_types=1);

namespace jaco\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use const jaco\PASSWORD;
use const jaco\SECRET_KEY;
use const jaco\USERNAME;

function login(string $username, string $password): string
{
    if ($username !== USERNAME || $password !== PASSWORD) {
        throw new \Exception('Login failed', 422);
    }

    $payload = [
        'iss' => 'com.jaco',
        'aud' => 'com.jaco',
        'iat' => time(),
        'nbf' => time(),
    ];

    return JWT::encode($payload, SECRET_KEY, 'HS512');
}

function is_logged_in(string $token): bool
{
    try {
        JWT::decode($token, new Key(SECRET_KEY, 'HS512'));
    } catch (\Throwable $t) {
        return false;
    }
    return true;
}

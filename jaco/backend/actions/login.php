<?php

namespace jaco\actions\login;

use Firebase\JWT\JWT;

function main(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username !== JACO_USERNAME || $password !== JACO_PASSWORD) {
        throw new \Exception('Login failed', 422);
    }

    $payload = [
        'iss' => 'com.jaco',
        'aud' => 'com.jaco',
        'iat' => time(),
        'nbf' => time(),
    ];

    $jwt = JWT::encode($payload, JACO_SECRET_KEY, 'HS256');

    return [
        'token' => $jwt,
    ];
}

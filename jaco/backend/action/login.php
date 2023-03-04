<?php

declare(strict_types=1);

namespace jaco\action\login;

use jaco\auth;
use jaco\data;
use jaco\request;
use jaco\response;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $raw = request\get_post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
    if (count($errors) > 0) {
        return [response\UNPROCESSABLE_CONTENT, $errors];
    }

    try {
        $token = auth\login($data['username'], $data['password']);
    } catch (\Throwable $t) {
        return [response\UNPROCESSABLE_CONTENT, [['title' => 'Validation Error', 'detail' => $t->getMessage()]]];
    }

    return [response\OK, ['token' => $token]];
}

/**
 * @return array<int, array{string, callable}>
 */
function filters(): array
{
    return [
        ['username', 'jaco\data\filter\string'],
        ['password', 'jaco\data\filter\string']
    ];
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['username', 'jaco\data\validator\required'],
        ['password', 'jaco\data\validator\required'],
    ];
}

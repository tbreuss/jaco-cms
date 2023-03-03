<?php

declare(strict_types=1);

namespace jaco\action\login;

use jaco\auth;
use jaco\data;
use jaco\request;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    // get raw user input
    $raw = request\post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
    if (count($errors) > 0) {
        return [422, $errors];
    }

    try {
        $token = auth\login($data['username'], $data['password']);
    } catch (\Throwable $t) {
        return [422, [['title' => 'Validation Error', 'detail' => $t->getMessage()]]];
    }

    return [200, ['token' => $token]];
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

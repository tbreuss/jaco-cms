<?php

declare(strict_types=1);

namespace jaco\action\login;

use jaco\auth;
use jaco\filter;
use jaco\request;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    // get raw user input
    $raw = request\post_values();

    // filter data
    $data = filter\all($raw, filters());

    // validate data
    $errors = validate\all($data, validators());
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
 * @return string[][]
 */
function filters(): array
{
    return [
        ['username', 'string'],
        ['password', 'string']
    ];
}

/**
 * @return string[][]
 */
function validators(): array
{
    return [
        ['username', 'required'],
        ['password', 'required'],
    ];
}

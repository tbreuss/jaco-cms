<?php

declare(strict_types=1);

namespace jaco\action\login;

use jaco\auth;
use jaco\request;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
function main(): array
{
    $data = request\post_values();
    $errors = validate\all($data, rules());
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
function rules(): array
{
    return [
        ['username', 'required'],
        ['password', 'required'],
    ];
}

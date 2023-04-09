<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\auth;
use jaco\data;
use jaco\request;
use jaco\response;

/**
 * @phpstan-return array<int,mixed>
 */
function action(): array
{
    $raw = request\get_post_values();

    $data = data\filter($raw, filters());

    $errors = data\validate($data, validators());
    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    $token = auth\authenticate($data['username'], $data['password']);

    if ($token === null) {
        return response\unprocessable_entity([['title' => 'Validation Error', 'detail' => 'Authentication failed']]);
    }

    return response\ok(['token' => $token]);
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

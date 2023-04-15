<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\auth;
use jaco\data;
use jaco\response;
use jaco\validate;

/**
 * @phpstan-return array<int,mixed>
 */
#[validate\request('/api/login/request.json')]
#[validate\response('/api/login/response.json')]
function action(array $apiInput): array
{
    $errors = data\validate($apiInput, validators());

    if (count($errors) > 0) {
        return response\unprocessable_entity($errors);
    }

    $token = auth\authenticate($apiInput['username'], $apiInput['password']);

    if ($token === null) {
        return response\unprocessable_entity([['title' => 'Validation Error', 'detail' => 'Authentication failed']]);
    }

    return response\ok(['token' => $token]);
}

/**
 * @return array<int, array{string, callable}>
 */
function validators(): array
{
    return [
        ['username', 'required'],
        ['password', 'required'],
    ];
}

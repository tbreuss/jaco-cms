<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\attribute;
use jaco\auth;
use jaco\data;
use jaco\http\response;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/login/request.json')]
#[attribute\validate_response('/api/login/response.json')]
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

function validators(): array
{
    return [
        ['username', 'required'],
        ['password', 'required'],
    ];
}

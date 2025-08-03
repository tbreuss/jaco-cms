<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = domain\system\authenticate(
        username: $bodyParams['username'],
        withPassword: $bodyParams['password'],
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok([
            'token' => $data['token'],
        ]);
    }

    return responder\api_errors($payload, [
        'username' => 'username',
        'withPassword' => 'password',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_request('/api/login/request.json')]
#[attribute\validate_response('/api/login/response.json')]
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

    if ($status === domain\SUCCESS) {
        return response\ok([
            'token' => $data['token'],
        ]);
    }

    return responder\api_errors($payload, [
        'username' => 'username',
        'withPassword' => 'password',
    ]);
}

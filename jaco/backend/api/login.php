<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\domain\system;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = system\authenticate(
        username: $bodyParams['username'],
        withPassword: $bodyParams['password'],
    );

    return responder($payload);
}

function responder(system\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok([
            'token' => $payload->data['token'],
        ]);
    }

    return responder\api_errors($payload, [
        'username' => 'username',
        'withPassword' => 'password',
    ]);
}

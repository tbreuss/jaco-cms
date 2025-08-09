<?php

declare(strict_types=1);

namespace jaco\api\login;

use jaco\domain\system\DomainPayload;
use jaco\core\http\Response;
use function jaco\domain\system\authenticate_user;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = authenticate_user(
        username: $bodyParams['username'],
        withPassword: $bodyParams['password'],
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'token' => $payload->data['token'],
        ]);
    }

    return api_errors($payload, [
        'username' => 'username',
        'withPassword' => 'password',
    ]);
}

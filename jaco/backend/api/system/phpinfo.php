<?php

declare(strict_types=1);

namespace jaco\api\system\phpinfo;

use jaco\domain\system;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(): array
{
    $payload = system\phpinfo();

    return responder($payload);
}

function responder(system\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok([
            'content' => $payload->data,
        ]);
    }

    return responder\api_errors($payload);
}

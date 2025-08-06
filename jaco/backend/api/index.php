<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\domain\system;
use jaco\lib\http\response;

function action(): array
{
    $payload = system\version();

    return responder($payload);
}

function responder(system\DomainPayload $payload): array
{
    return response\ok([
        'name' => $payload->data['name'],
        'version' => $payload->data['version'],
    ]);
}

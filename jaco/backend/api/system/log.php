<?php

declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\domain\system;
use jaco\lib\http\response;

function action(): array
{
    $payload = system\log(
        size: 1000
    );

    return responder($payload);
}

function responder(system\DomainPayload $payload): array
{
    return response\ok($payload->data);
}

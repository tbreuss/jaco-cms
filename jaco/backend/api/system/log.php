<?php

declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\domain;
use jaco\http\response;

function action(): array
{
    $payload = domain\system\log();

    return responder($payload);
}

function responder(array $payload): array
{
    [, $data] = $payload;

    return response\ok($data);
}

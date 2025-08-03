<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\domain;
use jaco\lib\http\response;

function action(): array
{
    $payload = domain\system\version();

    return responder($payload);
}

function responder(array $payload): array
{
    [, $data] = $payload;

    return response\ok([
        'name' => $data['name'],
        'version' => $data['version'],
    ]);
}

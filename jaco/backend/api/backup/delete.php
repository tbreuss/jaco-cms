<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\api\responder;
use jaco\domain;
use jaco\lib\http\response;

function action(array $bodyParams): array
{
    $payload = domain\backup\delete(
        file: $bodyParams['file']
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok();
    }

    return responder\errors($payload, [
        'file' => 'file',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\api\responder;
use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;

function action(array $bodyParams): array
{
    $payload = domain\page\delete(
        file: $bodyParams['fileName']
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok();
    }

    return responder\errors($payload, [
        'file' => 'fileName',
    ]);
}

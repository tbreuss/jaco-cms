<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = domain\backup\restore(
        file: $bodyParams['file']
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status] = $payload;

    if ($status === payload\SUCCESS) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

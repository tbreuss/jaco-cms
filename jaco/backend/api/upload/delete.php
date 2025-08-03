<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = domain\upload\delete(
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

    return responder\api_errors($payload, [
        'file' => 'fileName',
    ]);
}

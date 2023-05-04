<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\api\responder;
use jaco\domain;
use jaco\http\response;

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

    if ($status === domain\SUCCESS) {
        return response\ok();
    }

    return responder\errors($payload, [
        'file' => 'fileName',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\api\responder;
use jaco\domain;
use jaco\lib\http\response;

function action(array $bodyParams): array
{
    $payload = domain\page\update(
        file: $bodyParams['fileName'],
        withContent: $bodyParams['html'],
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status] = $payload;

    if ($status === domain\SUCCESS) {
        return response\created();
    }

    return responder\errors($payload, [
        'file' => 'fileName',
        'withContent' => 'html',
    ]);
}

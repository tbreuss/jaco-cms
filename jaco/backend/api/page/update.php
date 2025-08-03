<?php

declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

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

    if ($status === payload\SUCCESS) {
        return response\created();
    }

    return responder\api_errors($payload, [
        'file' => 'fileName',
        'withContent' => 'html',
    ]);
}

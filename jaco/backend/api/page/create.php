<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = domain\page\create(
        file: $bodyParams['fileName'],
        fromTemplate: $bodyParams['pageTemplate'],
        withTitle: $bodyParams['pageTitle']
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
        'fromTemplate' => 'pageTemplate',
        'withTitle' => 'pageTitle',
    ]);
}

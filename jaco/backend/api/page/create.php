<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\domain\page;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = page\create(
        file: $bodyParams['fileName'],
        fromTemplate: $bodyParams['pageTemplate'],
        withTitle: $bodyParams['pageTitle']
    );

    return responder($payload);
}

function responder(page\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\created();
    }

    return responder\api_errors($payload, [
        'file' => 'fileName',
        'fromTemplate' => 'pageTemplate',
        'withTitle' => 'pageTitle',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_request('/api/page/create/request.json')]
#[attribute\validate_response('/api/null.json')]
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

    if ($status === domain\SUCCESS) {
        return response\created();
    }

    return responder\api_errors($payload, [
        'file' => 'fileName',
        'fromTemplate' => 'pageTemplate',
        'withTitle' => 'pageTitle',
    ]);
}

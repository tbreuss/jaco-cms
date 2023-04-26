<?php

declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

#[attribute\validate_request('/api/page/update/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $bodyParams): array
{
    $payload = domain\page\update([
        'file' => $bodyParams['fileName'],
        'content' => $bodyParams['html'],
    ]);

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
        'content' => 'html',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\domain\page;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = page\delete(
        file: $bodyParams['fileName']
    );

    return responder($payload);
}

function responder(page\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'fileName',
    ]);
}

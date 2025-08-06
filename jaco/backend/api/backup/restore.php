<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\domain\backup;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = backup\restore(
        file: $bodyParams['file']
    );

    return responder($payload);
}

function responder(backup\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

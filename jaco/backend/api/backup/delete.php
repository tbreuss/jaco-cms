<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\domain\backup;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = backup\delete(
        file: $bodyParams['file']
    );

    return responder($payload);
}

function responder(backup\BackupPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

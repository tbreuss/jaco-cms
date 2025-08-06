<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\domain\upload;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = upload\delete(
        file: $bodyParams['fileName']
    );

    return responder($payload);
}

function responder(upload\UploadPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'fileName',
    ]);
}

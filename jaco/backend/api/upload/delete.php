<?php

declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\domain\upload\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\upload\delete_upload;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = delete_upload(
        file: $bodyParams['fileName']
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'fileName',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\domain\backup\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\backup\restore_backup;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = restore_backup(
        file: $bodyParams['file']
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

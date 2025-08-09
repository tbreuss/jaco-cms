<?php declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\domain\backup\DomainPayload;
use jaco\core\http\Response;

use function jaco\domain\backup\delete_backup;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = delete_backup(
        file: $bodyParams['file'],
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

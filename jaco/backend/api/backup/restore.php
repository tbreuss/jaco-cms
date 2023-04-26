<?php

declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_request('/api/backup/restore/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $bodyParams): array
{
    $payload = domain\backup\restore([
        'file' => $bodyParams['file'],
    ]);

    return responder($payload);
}

function responder(array $payload): array
{
    [$status] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok();
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

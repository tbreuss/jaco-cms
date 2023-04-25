<?php

declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/backup/delete/request.json')]
#[attribute\validate_response('/api/null.json')]
function action(array $bodyParams): array
{
    $payload = domain\backup\delete([
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

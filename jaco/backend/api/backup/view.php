<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\domain\backup;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $file): array
{
    $payload = backup\find(
        file: $file
    );

    return responder($payload);
}

function responder(backup\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok([
            'file' => $payload->data['file'],
            'name' => $payload->data['name'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
            'backedUp' => $payload->data['backedUp'],
            'content' => $payload->data['content'],
            'type' => $payload->data['type'],
        ]);
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

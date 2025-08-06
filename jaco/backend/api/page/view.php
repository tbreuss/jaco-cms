<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\domain\page;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $file): array
{
    $payload = page\find(
        file: $file
    );

    return responder($payload);
}

function responder(page\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok([
            'file' => $payload->data['file'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
            'content' => $payload->data['content'],
        ]);
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

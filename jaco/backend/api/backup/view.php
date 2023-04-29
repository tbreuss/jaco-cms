<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_response('/api/backup/view/response.json')]
function action(string $file): array
{
    $payload = domain\backup\find(
        file: $file
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\ok([
            'file' => $data['file'],
            'name' => $data['name'],
            'title' => $data['title'],
            'created' => $data['created'],
            'backedUp' => $data['backedUp'],
            'content' => $data['content'],
            'type' => $data['type'],
        ]);
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

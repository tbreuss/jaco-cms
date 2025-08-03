<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

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

    if ($status === payload\SUCCESS) {
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

<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(string $file): array
{
    $payload = domain\page\find(
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
            'title' => $data['title'],
            'created' => $data['created'],
            'content' => $data['content'],
        ]);
    }

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

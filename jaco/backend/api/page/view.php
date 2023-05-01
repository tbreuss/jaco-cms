<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\api\responder;
use jaco\domain;
use jaco\http\response;

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

    if ($status === domain\SUCCESS) {
        return response\ok([
            'file' => $data['file'],
            'title' => $data['title'],
            'created' => $data['created'],
            'content' => $data['content'],
        ]);
    }

    return responder\errors($payload, [
        'file' => 'file',
    ]);
}

<?php

declare(strict_types=1);

namespace jaco\api\page\view;

use jaco\api\responder;
use jaco\attribute;
use jaco\domain;
use jaco\http\response;

#[attribute\validate_response('/api/page/view/response.json')]
function action(string $file): array
{
    $payload = domain\page\find_one([
        'file' => $file
    ]);

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

    return responder\api_errors($payload, [
        'file' => 'file',
    ]);
}

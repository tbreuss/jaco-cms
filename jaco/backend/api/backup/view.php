<?php

declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_response('/api/backup/view/response.json')]
function action(string $file): array
{
    $payload = domain\backup\find_one([
        'file' => $file,
    ]);

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

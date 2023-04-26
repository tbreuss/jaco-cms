<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

#[attribute\validate_response('/api/backup/list/response.json')]
function action(string $filter = '', string $sort = '-created'): array
{
    $payload = domain\backup\find_all([
        'filter' => $filter,
        'sort' => $sort
    ]);

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        [$items, $filter, $sort] = $data;
        return response\ok([
            'items' => $items,
            'filter' => $filter,
            'sort' => $sort,
        ]);
    }

    return responder\api_errors($payload, [
        'filter' => 'filter',
        'sort' => 'sort',
    ]);
}

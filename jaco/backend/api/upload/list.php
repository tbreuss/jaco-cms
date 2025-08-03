<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\domain;
use jaco\lib\http\response;
use jaco\lib\payload;
use jaco\lib\responder;

function action(string $filter = '', string $sort = '-created'): array
{
    $payload = domain\upload\find_all(
        filteredBy: $filter,
        sortedBy: $sort
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === payload\SUCCESS) {
        [$items, $filter, $sort] = $data;
        return response\ok([
            'items' => $items,
            'filter' => $filter,
            'sort' => $sort,
        ]);
    }

    return responder\api_errors($payload, [
        'filteredBy' => 'filter',
        'sortedBy' => 'sort',
    ]);
}

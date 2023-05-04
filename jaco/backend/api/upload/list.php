<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\api\responder;
use jaco\domain;
use jaco\http\response;

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

    if ($status === domain\SUCCESS) {
        [$items, $filter, $sort] = $data;
        return response\ok([
            'items' => $items,
            'filter' => $filter,
            'sort' => $sort,
        ]);
    }

    return responder\errors($payload, [
        'filteredBy' => 'filter',
        'sortedBy' => 'sort',
    ]);
}

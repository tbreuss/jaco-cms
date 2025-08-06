<?php

declare(strict_types=1);

namespace jaco\api\page\list;

use jaco\domain\page;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $filter = '', string $sort = '-created'): array
{
    $payload = page\find_all(
        filteredBy: $filter,
        sortedBy: $sort
    );

    return responder($payload);
}

function responder(page\PagePayload $payload): array
{
    if ($payload->isSuccess) {
        [$items, $filter, $sort] = $payload->data;
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

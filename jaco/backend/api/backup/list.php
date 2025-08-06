<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\domain\backup;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $filter = '', string $sort = '-created'): array
{
    $payload = backup\find_all(
        filteredBy: $filter,
        sortedBy: $sort
    );

    return responder($payload);
}

function responder(backup\BackupPayload $payload): array
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

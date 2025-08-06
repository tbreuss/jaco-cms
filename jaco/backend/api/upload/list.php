<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\domain\upload;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $filter = '', string $sort = '-created'): array
{
    $payload = upload\find_all(
        filteredBy: $filter,
        sortedBy: $sort
    );

    return responder($payload);
}

function responder(upload\UploadPayload $payload): array
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

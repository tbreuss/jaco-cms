<?php

declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\domain\backup\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\backup\find_backups;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(string $filter = '', string $sort = '-created'): Response
{
    $payload = find_backups(
        filteredBy: $filter,
        sortedBy: $sort
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        [$items, $filter, $sort] = $payload->data;
        return json_response([
            'items' => $items,
            'filter' => $filter,
            'sort' => $sort,
        ]);
    }

    return api_errors($payload, [
        'filteredBy' => 'filter',
        'sortedBy' => 'sort',
    ]);
}

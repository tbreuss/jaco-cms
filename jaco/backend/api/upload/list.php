<?php

declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\domain\upload\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\upload\find_uploads;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(string $filter = '', string $sort = '-created'): Response
{
    $payload = find_uploads(
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

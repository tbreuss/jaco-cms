<?php

declare(strict_types=1);

namespace jaco\api\page\list;

use jaco\domain\page\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\page\find_pages;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(string $filter = '', string $sort = '-created'): Response
{
    $payload = find_pages(
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

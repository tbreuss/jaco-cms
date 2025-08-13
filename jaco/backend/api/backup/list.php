<?php declare(strict_types=1);

namespace jaco\api\backup\list;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\backup\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\backup\find_backups;

function action(Request $request): Response
{
    $payload = find_backups(
        filteredBy: $request->query->strVal('filter'),
        sortedBy: $request->query->strVal('sort', '-created'),
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

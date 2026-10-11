<?php declare(strict_types=1);

namespace jaco\api\upload\list;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\upload\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\upload\find_uploads;

function action(Config $config, Request $request): Response
{
    $payload = find_uploads(
        $config,
        filteredBy: $request->query->strVal('filter', ''),
        sortedBy: $request->query->strVal('sort', '-created'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'items' => $payload->data['items'],
            'filter' => $payload->data['filter'],
            'sort' => $payload->data['sort'],
        ]);
    }

    return api_errors($payload, [
        'filteredBy' => 'filter',
        'sortedBy' => 'sort',
    ]);
}

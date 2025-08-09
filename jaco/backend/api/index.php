<?php

declare(strict_types=1);

namespace jaco\api\index;

use jaco\domain\system\DomainPayload;
use jaco\core\http\Response;
use function jaco\domain\system\get_version;
use function jaco\core\http\json_response;

function action(): Response
{
    $payload = get_version();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    return json_response([
        'name' => $payload->data['name'],
        'version' => $payload->data['version'],
    ]);
}

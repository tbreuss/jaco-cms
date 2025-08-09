<?php

declare(strict_types=1);

namespace jaco\api\system\phpinfo;

use jaco\domain\system\DomainPayload;
use jaco\lib\http\Response;
use function jaco\domain\system\get_phpinfo;
use function jaco\lib\http\json_response;
use function jaco\lib\responder\api_errors;

function action(): Response
{
    $payload = get_phpinfo();

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'content' => $payload->data,
        ]);
    }

    return api_errors($payload);
}

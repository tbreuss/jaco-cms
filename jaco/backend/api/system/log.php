<?php

declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\domain\system\DomainPayload;
use jaco\core\http\Response;
use function jaco\domain\system\get_log_entries;
use function jaco\core\http\json_response;

function action(): Response
{
    $payload = get_log_entries(
        size: 1000
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    return json_response($payload->data);
}

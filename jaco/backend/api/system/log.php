<?php declare(strict_types=1);

namespace jaco\api\system\log;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\system\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\domain\system\get_log_entries;

function action(Config $config): Response
{
    $payload = get_log_entries(
        $config,
        size: 1000,
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    return json_response($payload->data);
}

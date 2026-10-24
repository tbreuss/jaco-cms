<?php declare(strict_types=1);

namespace jaco\api\system\log\clear;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\clear_error_log;

function action(Config $config): Response
{

    $payload = clear_error_log($config);

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload);
}

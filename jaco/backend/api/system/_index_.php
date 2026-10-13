<?php declare(strict_types=1);

/**
 * Entry point of the system api. action() runs the domain function,
 * responder() maps its payload to a response, the sub namespaces hold
 * one endpoint each.
 */

namespace jaco\api\system;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\get_environment;

function action(Config $config): Response
{
    $payload = get_environment($config);

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'classes' => $payload->data['classes'],
            'config' => $payload->data['config'],
            'constants' => $payload->data['constants'],
            'functions' => $payload->data['functions'],
        ]);
    }

    return api_errors($payload);
}

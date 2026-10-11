<?php declare(strict_types=1);

namespace jaco\api\index;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\system\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\get_version;

function action(Config $config): Response
{
    $payload = get_version($config);

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'name' => $payload->data['name'],
            'version' => $payload->data['version'],
        ]);
    }

    return api_errors($payload);
}

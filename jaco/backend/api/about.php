<?php declare(strict_types=1);

namespace jaco\api\about;

use jaco\core\config\Config;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\get_version;

/**
 * Version data for the About page. Unlike the public index route this exposes the
 * PHP version, so it needs a valid token; every role may read it.
 */
function action(Config $config): Response
{
    $payload = get_version($config);

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'name' => $payload->data['name'],
            'version' => $payload->data['version'],
            'phpVersion' => $payload->data['phpVersion'],
        ]);
    }

    return api_errors($payload);
}

<?php declare(strict_types=1);

namespace jaco\api\system\update;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\update\perform_update;

function action(Config $config, Request $request): Response
{
    // allow the request to run as long as it needs
    @set_time_limit(0);
    @ignore_user_abort(true);

    $payload = perform_update(
        $config,
        $request->post->strVal('downloadUrl') ?: null,
        $request->post->strVal('version') ?: null,
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload);
}

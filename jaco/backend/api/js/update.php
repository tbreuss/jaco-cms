<?php declare(strict_types=1);

namespace jaco\api\js\update;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\js\update_js;

function action(Config $config, Request $request): Response
{
    if (($blocked = maintenance_response($config->maintenanceMode)) !== null) {
        return $blocked;
    }

    $payload = update_js(
        $config,
        file: $request->post->strVal('fileName'),
        withContent: $request->post->strVal('js'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(status: Status::CREATED);
    }

    return api_errors($payload, [
        'file' => 'fileName',
        'withContent' => 'js',
    ]);
}

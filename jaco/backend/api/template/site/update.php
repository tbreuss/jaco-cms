<?php declare(strict_types=1);

namespace jaco\api\template\site\update;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\template\update_site_template;

function action(Config $config, Request $request): Response
{
    if (($blocked = maintenance_response($config->maintenanceMode)) !== null) {
        return $blocked;
    }

    return responder(update_site_template(
        $config,
        $request->post->strVal('fileName'),
        $request->post->strVal('content'),
        $request->post->val('applyToAll') === true || $request->post->strVal('applyToAll') === '1',
    ));
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload, [
        'file' => 'fileName',
        'content' => 'content',
    ]);
}

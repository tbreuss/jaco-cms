<?php declare(strict_types=1);

namespace jaco\api\template\site\apply;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\apply_site_template_to_all;

function action(Config $config, Request $request): Response
{
    return responder(apply_site_template_to_all(
        $config,
        $request->post->strVal('fileName'),
    ));
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload, [
        'file' => 'fileName',
    ]);
}

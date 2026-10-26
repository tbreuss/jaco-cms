<?php declare(strict_types=1);

namespace jaco\api\template\site\preview;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\template\preview_site_template;

function action(Config $config, Request $request): Response
{
    return responder(preview_site_template($config, $request->post->strVal('fileName')));
}

function responder(Payload|array $payload): Response
{
    if ($payload instanceof Payload) {
        return api_errors($payload, ['file' => 'fileName']);
    }

    return json_response($payload);
}

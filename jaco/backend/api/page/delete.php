<?php declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\page\delete_page;

function action(Config $config, Request $request): Response
{

    $payload = delete_page(
        $config,
        file: $request->post->strVal('fileName'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'fileName',
    ]);
}

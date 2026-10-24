<?php declare(strict_types=1);

namespace jaco\api\css\update;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\css\update_css;

function action(Config $config, Request $request): Response
{

    $payload = update_css(
        $config,
        file: $request->post->strVal('fileName'),
        withContent: $request->post->strVal('css'),
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
        'withContent' => 'css',
    ]);
}

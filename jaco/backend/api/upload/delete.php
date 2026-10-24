<?php declare(strict_types=1);

namespace jaco\api\upload\delete;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\upload\delete_upload;

function action(Config $config, Request $request): Response
{

    $payload = delete_upload(
        $config,
        file: $request->post->val('fileName'),
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

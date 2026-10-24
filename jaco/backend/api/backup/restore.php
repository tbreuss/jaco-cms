<?php declare(strict_types=1);

namespace jaco\api\backup\restore;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\backup\restore_backup;

function action(Config $config, Request $request): Response
{

    $payload = restore_backup(
        $config,
        file: $request->post->strVal('file'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

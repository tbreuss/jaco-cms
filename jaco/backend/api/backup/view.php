<?php declare(strict_types=1);

namespace jaco\api\backup\view;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\backup\find_backup;

function action(Config $config, Request $request): Response
{
    $payload = find_backup(
        $config,
        file: $request->query->strVal('file'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'file' => $payload->data['file'],
            'name' => $payload->data['name'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
            'backedUp' => $payload->data['backedUp'],
            'content' => $payload->data['content'],
            'type' => $payload->data['type'],
        ]);
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

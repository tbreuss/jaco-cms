<?php declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\page\copy_page;

function action(Config $config, Request $request): Response
{
    if (($blocked = maintenance_response($config->maintenanceMode)) !== null) {
        return $blocked;
    }

    $payload = copy_page(
        $config,
        file: $request->post->strVal('srcFileName'),
        toFile: $request->post->strVal('dstFileName'),
        withTitle: $request->post->strVal('dstPageTitle'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response([
            'file' => $payload->data['file'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
        ], Status::CREATED);
    }

    return api_errors($payload, [
        'file' => 'srcFileName',
        'toFile' => 'dstFileName',
        'withTitle' => 'dstPageTitle',
    ]);
}

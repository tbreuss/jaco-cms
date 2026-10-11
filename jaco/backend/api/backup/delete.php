<?php declare(strict_types=1);

namespace jaco\api\backup\delete;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\backup\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\backup\delete_backup;

function action(Config $config, Request $request): Response
{
    if (($blocked = maintenance_response($config->maintenanceMode)) !== null) {
        return $blocked;
    }

    $payload = delete_backup(
        $config,
        file: $request->post->strVal('file'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'file',
    ]);
}

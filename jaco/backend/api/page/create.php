<?php declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\page\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\page\create_page;

function action(Config $config, Request $request): Response
{
    if (($blocked = maintenance_response($config->maintenanceMode)) !== null) {
        return $blocked;
    }

    $payload = create_page(
        $config,
        file: $request->post->strVal('fileName'),
        fromTemplate: $request->post->strVal('pageTemplate'),
        withTitle: $request->post->strVal('pageTitle'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response(status: Status::CREATED);
    }

    return api_errors($payload, [
        'file' => 'fileName',
        'fromTemplate' => 'pageTemplate',
        'withTitle' => 'pageTitle',
    ]);
}

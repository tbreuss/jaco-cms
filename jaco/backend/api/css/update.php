<?php declare(strict_types=1);

namespace jaco\api\css\update;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\css\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\responder\maintenance_response;
use function jaco\domain\css\update_css;

function action(Request $request): Response
{
    if (($blocked = maintenance_response()) !== null) {
        return $blocked;
    }

    $payload = update_css(
        file: $request->post->strVal('fileName'),
        withContent: $request->post->strVal('css'),
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
        'withContent' => 'css',
    ]);
}

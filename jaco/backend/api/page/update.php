<?php declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\page\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\page\update_page;

function action(Request $request): Response
{
    $payload = update_page(
        file: $request->post->strVal('fileName'),
        withContent: $request->post->strVal('html'),
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
        'withContent' => 'html',
        'withTitle' => 'pageTitle',
    ]);
}

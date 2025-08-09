<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\domain\page\DomainPayload;
use jaco\core\http\Response;
use jaco\core\http\Status;
use function jaco\domain\page\copy_page;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = copy_page(
        file: $bodyParams['srcFileName'],
        toFile: $bodyParams['dstFileName'],
        withTitle: $bodyParams['dstPageTitle'],
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
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

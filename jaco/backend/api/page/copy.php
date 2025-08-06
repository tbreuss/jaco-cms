<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\domain\page;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(array $bodyParams): array
{
    $payload = page\copy(
        file: $bodyParams['srcFileName'],
        toFile: $bodyParams['dstFileName'],
        withTitle: $bodyParams['dstPageTitle'],
    );

    return responder($payload);
}

function responder(page\DomainPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\created([
            'file' => $payload->data['file'],
            'title' => $payload->data['title'],
            'created' => $payload->data['created'],
        ]);
    }

    return responder\api_errors($payload, [
        'file' => 'srcFileName',
        'toFile' => 'dstFileName',
        'withTitle' => 'dstPageTitle',
    ]);
}

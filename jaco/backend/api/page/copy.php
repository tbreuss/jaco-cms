<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\api\responder;
use jaco\domain;
use jaco\lib\http\response;

function action(array $bodyParams): array
{
    $payload = domain\page\copy(
        file: $bodyParams['srcFileName'],
        toFile: $bodyParams['dstFileName'],
        withTitle: $bodyParams['dstPageTitle'],
    );

    return responder($payload);
}

function responder(array $payload): array
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        return response\created([
            'file' => $data['file'],
            'title' => $data['title'],
            'created' => $data['created'],
        ]);
    }

    return responder\errors($payload, [
        'file' => 'srcFileName',
        'toFile' => 'dstFileName',
        'withTitle' => 'dstPageTitle',
    ]);
}

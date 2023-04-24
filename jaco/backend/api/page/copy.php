<?php

declare(strict_types=1);

namespace jaco\api\page\copy;

use jaco\attribute;
use jaco\domain;
use jaco\http\response;
use jaco\responder;

/**
 * @phpstan-return array<int,mixed>
 */
#[attribute\validate_request('/api/page/copy/request.json')]
#[attribute\validate_response('/api/page/copy/response.json')]
function action(array $bodyParams): array
{
    $payload = domain\page\copy([
        'srcFile' => $bodyParams['srcFileName'],
        'dstFile' => $bodyParams['dstFileName'],
        'dstTitle' => $bodyParams['dstPageTitle'],
    ]);

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

    return responder\api_errors($payload, [
        'srcFile' => 'srcFileName',
        'dstFile' => 'dstFileName',
        'dstTitle' => 'dstPageTitle',
    ]);
}

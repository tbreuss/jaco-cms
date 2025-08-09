<?php

declare(strict_types=1);

namespace jaco\api\page\delete;

use jaco\domain\page\DomainPayload;
use jaco\core\http\Response;
use function jaco\domain\page\delete_page;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = delete_page(
        file: $bodyParams['fileName']
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'file' => 'fileName',
    ]);
}

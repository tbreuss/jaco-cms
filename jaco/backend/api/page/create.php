<?php

declare(strict_types=1);

namespace jaco\api\page\create;

use jaco\domain\page\DomainPayload;
use jaco\core\http\Response;
use jaco\core\http\Status;
use function jaco\domain\page\create_page;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = create_page(
        file: $bodyParams['fileName'],
        fromTemplate: $bodyParams['pageTemplate'],
        withTitle: $bodyParams['pageTitle']
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

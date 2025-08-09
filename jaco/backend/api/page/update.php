<?php declare(strict_types=1);

namespace jaco\api\page\update;

use jaco\domain\page\DomainPayload;
use jaco\core\http\Response;
use jaco\core\http\Status;

use function jaco\domain\page\update_page;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(array $bodyParams): Response
{
    $payload = update_page(
        file: $bodyParams['fileName'],
        withContent: $bodyParams['html'],
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
    ]);
}

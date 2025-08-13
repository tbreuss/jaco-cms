<?php declare(strict_types=1);

namespace jaco\api\translation;

use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\translation\DomainPayload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\translation\load_translations;

function action(Request $request): Response
{
    $payload = load_translations(
        forLanguage: $request->query->strVal('language', 'en'),
    );

    return responder($payload);
}

function responder(DomainPayload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response($payload->data);
    }

    return api_errors($payload);
}

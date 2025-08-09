<?php declare(strict_types=1);

namespace jaco\api\translation;

use jaco\domain\translation\DomainPayload;
use jaco\core\http\Response;

use function jaco\domain\translation\load_translations;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;

function action(string $language): Response
{
    $payload = load_translations(
        forLanguage: $language,
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

<?php

declare(strict_types=1);

namespace jaco\api\translation;

use jaco\domain\translation;
use jaco\lib\http\response;
use jaco\lib\responder;

function action(string $language): array
{
    $payload = translation\load_all(
        forLanguage: $language
    );

    return responder($payload);
}

function responder(translation\TranslationPayload $payload): array
{
    if ($payload->isSuccess) {
        return response\ok($payload->data);
    }

    return responder\api_errors($payload);
}

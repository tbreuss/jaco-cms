<?php declare(strict_types=1);

namespace jaco\api\translation;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\domain\translation\load_translations;

function action(Config $config, Request $request): Response
{
    $payload = load_translations(
        $config,
        forLanguage: $request->query->strVal('language', 'en'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        // translations are a map, encode an empty list as an empty object
        return json_response((object) $payload->data);
    }

    return api_errors($payload);
}

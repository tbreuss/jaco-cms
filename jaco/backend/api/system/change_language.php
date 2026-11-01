<?php declare(strict_types=1);

namespace jaco\api\system\change_language;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\auth\read_token;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\router\get_authorization_header;
use function jaco\domain\system\change_language;

function action(Config $config, Request $request): Response
{
    $claims = read_token($config, trim(substr(get_authorization_header($request), strlen('Bearer'))));

    $payload = change_language(
        $config,
        username: (string) ($claims['user'] ?? ''),
        language: $request->post->strVal('language'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        // the new token already carries the language, so the session switches
        // without asking for the password again
        return json_response($payload->data);
    }

    return api_errors($payload, [
        'language' => 'language',
    ]);
}

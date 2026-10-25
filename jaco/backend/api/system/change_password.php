<?php declare(strict_types=1);

namespace jaco\api\system\change_password;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\domain\Payload;

use function jaco\core\auth\read_token;
use function jaco\core\http\json_response;
use function jaco\core\responder\api_errors;
use function jaco\core\router\get_authorization_header;
use function jaco\domain\system\change_password;

function action(Config $config, Request $request): Response
{
    // exempt from maintenance mode through the route table: changing the password
    // is the way back in when something went wrong, and the owner needs it most
    // while the mode is on
    $claims = read_token($config, trim(substr(get_authorization_header($request), strlen('Bearer'))));

    $payload = change_password(
        $config,
        username: (string) ($claims['user'] ?? ''),
        withPassword: $request->post->strVal('withPassword'),
        withNewPassword: $request->post->strVal('withNewPassword'),
        address: $request->server->strVal('REMOTE_ADDR'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        return json_response();
    }

    return api_errors($payload, [
        'withPassword' => 'withPassword',
        'withNewPassword' => 'withNewPassword',
    ]);
}

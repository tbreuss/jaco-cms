<?php declare(strict_types=1);

namespace jaco\api\login;

use jaco\core\config\Config;
use jaco\core\http\Request;
use jaco\core\http\Response;
use jaco\core\http\Status;
use jaco\domain\Payload;

use function jaco\core\http\json_response;
use function jaco\core\router\check_authorization;
use function jaco\core\router\get_authorization_header;
use function jaco\core\responder\api_errors;
use function jaco\domain\system\authenticate_user;

function action(Config $config, Request $request): Response
{
    // already authenticated: no need to log in again
    $authorizationHeader = get_authorization_header($request);
    if ($authorizationHeader !== '') {
        $claims = check_authorization($config, $authorizationHeader);
        if ($claims !== null) {
            return json_response([[
                'title' => 'Already authenticated',
                'detail' => 'You are already logged in.',
            ]], Status::CONFLICT);
        }
    }

    $payload = authenticate_user(
        $config,
        username: $request->post->strVal('username'),
        withPassword: $request->post->strVal('password'),
        withTotpCode: $request->post->strVal('totpCode'),
        address: $request->server->strVal('REMOTE_ADDR'),
    );

    return responder($payload);
}

function responder(Payload $payload): Response
{
    if ($payload->isSuccess) {
        if (isset($payload->data['totpRequired'])) {
            return json_response(['totpRequired' => true]);
        }

        return json_response([
            'token' => $payload->data['token'],
        ]);
    }

    return api_errors($payload, [
        'username' => 'username',
        'withPassword' => 'password',
        'totpCode' => 'totpCode',
    ]);
}

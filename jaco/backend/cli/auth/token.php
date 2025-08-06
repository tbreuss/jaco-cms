<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\domain\system;
use jaco\lib\responder;

function command(string $user, string $pass): void
{
    $payload = system\authenticate(
        username: $user,
        withPassword: $pass,
    );

    responder($payload, $user);
}

function responder(system\SystemPayload $payload, string $user): void
{
    if ($payload->isSuccess) {
        fwrite(STDOUT, sprintf('The token for user "%s" is:', $user) . PHP_EOL);
        fwrite(STDOUT, $payload->data['token'] . PHP_EOL);
        exit(0);
    }

    responder\cli_errors($payload->status, $payload->data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

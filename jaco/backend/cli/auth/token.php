<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\domain\system\DomainPayload;
use function jaco\domain\system\authenticate_user;
use function jaco\lib\responder\cli_errors;

function command(string $user, string $pass): void
{
    $payload = authenticate_user(
        username: $user,
        withPassword: $pass,
    );

    responder($payload, $user);
}

function responder(DomainPayload $payload, string $user): void
{
    if ($payload->isSuccess) {
        fwrite(STDOUT, sprintf('The token for user "%s" is:', $user) . PHP_EOL);
        fwrite(STDOUT, $payload->data['token'] . PHP_EOL);
        exit(0);
    }

    cli_errors($payload->status, $payload->data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

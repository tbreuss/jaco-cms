<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\cli\responder;
use jaco\domain;

function command(string $user, string $pass): void
{
    $payload = domain\system\authenticate(
        username: $user,
        withPassword: $pass,
    );

    responder($payload, $user);
}

function responder(array $payload, string $user): void
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        fwrite(STDOUT, sprintf('The token for user "%s" is:', $user) . PHP_EOL);
        fwrite(STDOUT, $data['token'] . PHP_EOL);
        exit(0);
    }

    responder\handle_errors($status, $data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

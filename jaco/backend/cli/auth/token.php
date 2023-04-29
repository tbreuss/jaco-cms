<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\cli\responder;
use jaco\domain;

function command(array $options = [], array $arguments = []): void
{
    $payload = domain\system\authenticate(
        username: $options['user'],
        withPassword: $options['pass'],
    );

    responder($payload, $options);
}

function responder(array $payload, array $options): void
{
    [$status, $data] = $payload;

    if ($status === domain\SUCCESS) {
        fwrite(STDOUT, sprintf('The token for user "%s" is:', $options['user']) . PHP_EOL);
        fwrite(STDOUT, $data['token'] . PHP_EOL);
        exit(0);
    }

    responder\handle_errors($status, $data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

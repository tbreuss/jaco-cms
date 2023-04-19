<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\cli;
use jaco\service;

const inputMapping = [
    'user' => 'username',
    'pass' => 'password',
];

function command(array $options = [], array $arguments = []): void
{
    $serviceInput = cli\transform_data($options, inputMapping);

    [$serviceStatus, $serviceOutput] = service\system\authenticate($serviceInput);

    if ($serviceStatus === service\SUCCESS) {
        fwrite(STDOUT, sprintf('The token for user %s is:', $options['user']) . PHP_EOL);
        fwrite(STDOUT, $serviceOutput['token'] . PHP_EOL);
        exit(0);
    }

    cli\handle_errors($serviceStatus, $serviceOutput, inputMapping);
}

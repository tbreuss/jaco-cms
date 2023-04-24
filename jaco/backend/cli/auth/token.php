<?php

declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\cli;
use jaco\domain;

const inputMapping = [
    'user' => 'username',
    'pass' => 'password',
];

function command(array $options = [], array $arguments = []): void
{
    $serviceInput = cli\transform_data($options, inputMapping);

    [$serviceStatus, $serviceOutput] = domain\system\authenticate($serviceInput);

    if ($serviceStatus === domain\SUCCESS) {
        fwrite(STDOUT, sprintf('The token for user %s is:', $options['user']) . PHP_EOL);
        fwrite(STDOUT, $serviceOutput['token'] . PHP_EOL);
        exit(0);
    }

    cli\handle_errors($serviceStatus, $serviceOutput, inputMapping);
}

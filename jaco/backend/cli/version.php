<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\domain;

function command(array $options = [], array $arguments = [])
{
    [, $serviceOutput] = domain\system\version();

    fwrite(STDOUT, $serviceOutput['name'] . ' ' . $serviceOutput['version'] . PHP_EOL);
}

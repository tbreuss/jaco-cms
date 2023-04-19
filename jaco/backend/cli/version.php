<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\service;

function command(array $options = [], array $arguments = [])
{
    [, $serviceOutput] = service\system\version();

    fwrite(STDOUT, $serviceOutput['name'] . ' ' . $serviceOutput['version'] . PHP_EOL);
}

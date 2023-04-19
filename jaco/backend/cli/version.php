<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\service;

function command(array $options = [], array $arguments = [])
{
    [, $serviceOutput] = service\system\version();

    fwrite(STDOUT, 'Version' . PHP_EOL);
    fwrite(STDOUT, '-name: ' . $serviceOutput['name'] . PHP_EOL);
    fwrite(STDOUT, '-version: ' . $serviceOutput['version'] . PHP_EOL);
}

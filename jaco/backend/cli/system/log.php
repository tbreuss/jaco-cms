<?php

declare(strict_types=1);

namespace jaco\cli\system\log;

use jaco\domain;

function command(int $size): void
{
    $payload = domain\system\log(
        size: $size
    );

    responder($payload);
}

function responder(array $payload): void
{
    [, $data] = $payload;

    array_walk($data, function(string $line) {
        echo $line . PHP_EOL;
    });
}

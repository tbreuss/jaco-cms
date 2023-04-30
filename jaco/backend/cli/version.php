<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\domain;

function command(): void
{
    $payload = domain\system\version();

    responder($payload);
}

function responder(array $payload): void
{
    [, $data] = $payload;

    fwrite(STDOUT, $data['name'] . ' ' . $data['version'] . PHP_EOL);
}

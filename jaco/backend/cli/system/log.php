<?php

declare(strict_types=1);

namespace jaco\cli\system\log;

use jaco\domain\system;

function command(int $size): void
{
    $payload = system\log(
        size: $size
    );

    responder($payload);
}

function responder(system\DomainPayload $payload): void
{
    foreach ($payload->data as $line) {
        echo $line . PHP_EOL;
    }
}

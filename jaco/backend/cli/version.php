<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\domain\system;

function command(): void
{
    $payload = system\version();

    responder($payload);
}

function responder(system\DomainPayload $payload): void
{
    fwrite(STDOUT, $payload->data['name'] . ' ' . $payload->data['version'] . PHP_EOL);
}

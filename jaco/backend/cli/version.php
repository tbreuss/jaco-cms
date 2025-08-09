<?php

declare(strict_types=1);

namespace jaco\cli\version;

use jaco\domain\system\DomainPayload;
use function jaco\domain\system\get_version;

function command(): void
{
    $payload = get_version();

    responder($payload);
}

function responder(DomainPayload $payload): void
{
    fwrite(STDOUT, $payload->data['name'] . ' ' . $payload->data['version'] . PHP_EOL);
}

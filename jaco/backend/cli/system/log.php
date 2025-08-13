<?php declare(strict_types=1);

namespace jaco\cli\system\log;

use jaco\domain\system\DomainPayload;

use function jaco\domain\system\get_log_entries;

function command(int $size): void
{
    $payload = get_log_entries(
        size: $size,
    );

    responder($payload);
}

function responder(DomainPayload $payload): void
{
    foreach ($payload->data as $line) {
        echo $line . PHP_EOL;
    }
}

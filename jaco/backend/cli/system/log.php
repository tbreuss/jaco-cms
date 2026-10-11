<?php declare(strict_types=1);

namespace jaco\cli\system\log;

use jaco\core\config\Config;
use jaco\domain\system\DomainPayload;

use function jaco\domain\system\get_log_entries;

function command(Config $config, int $size): void
{
    $payload = get_log_entries(
        $config,
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

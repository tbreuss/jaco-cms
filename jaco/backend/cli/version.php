<?php declare(strict_types=1);

namespace jaco\cli\version;

use jaco\core\config\Config;
use jaco\domain\system\DomainPayload;

use function jaco\domain\system\get_version;

function command(Config $config): void
{
    $payload = get_version($config);

    responder($payload);
}

function responder(DomainPayload $payload): void
{
    fwrite(STDOUT, $payload->data['name'] . ' ' . $payload->data['version'] . PHP_EOL);
}

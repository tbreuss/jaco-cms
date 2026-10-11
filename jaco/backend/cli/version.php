<?php declare(strict_types=1);

namespace jaco\cli\version;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\domain\system\get_version;

function command(Config $config): void
{
    $payload = get_version($config);

    responder($payload);
}

function responder(Payload $payload): void
{
    fwrite(STDOUT, $payload->data['name'] . ' ' . $payload->data['version'] . PHP_EOL);
}

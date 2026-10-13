<?php declare(strict_types=1);

namespace jaco\cli\version;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\domain\system\get_version;

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function command(Config $config): array
{
    $payload = get_version($config);

    return responder($payload);
}

/**
 * @return array{string, int} output and exit code
 */
function responder(Payload $payload): array
{
    return [$payload->data['name'] . ' ' . $payload->data['version'] . PHP_EOL, 0];
}

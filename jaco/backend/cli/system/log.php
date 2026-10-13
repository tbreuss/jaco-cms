<?php declare(strict_types=1);

namespace jaco\cli\system\log;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\domain\system\get_log_entries;

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function command(Config $config, int $size): array
{
    $payload = get_log_entries(
        $config,
        size: $size,
    );

    return responder($payload);
}

/**
 * @return array{string, int} output and exit code
 */
function responder(Payload $payload): array
{
    $output = '';
    foreach ($payload->data as $line) {
        $output .= $line . PHP_EOL;
    }
    return [$output, 0];
}

<?php declare(strict_types=1);

namespace jaco\cli\system\check_update;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\domain\update\check_update;

/**
 * @return array{string, int} output and exit code
 */
function command(Config $config): array
{
    $payload = check_update($config);

    return responder($payload);
}

/**
 * @return array{string, int} output and exit code
 */
function responder(Payload $payload): array
{
    if (!$payload->isSuccess) {
        $msg = is_array($payload->data) && isset($payload->data[0][1]) ? (string) $payload->data[0][1] : 'Check failed.';
        return [$msg . PHP_EOL, 1];
    }

    $d = $payload->data;

    if (!$d['available']) {
        return ['Already up to date (' . $d['current'] . ').' . PHP_EOL, 0];
    }

    return [
        'Update available: ' . $d['current'] . ' -> ' . $d['latest'] . PHP_EOL
        . 'Download: ' . $d['downloadUrl'] . PHP_EOL,
        0,
    ];
}

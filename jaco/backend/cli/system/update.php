<?php declare(strict_types=1);

namespace jaco\cli\system\update;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\domain\update\perform_update;

/**
 * @return array{string, int} output and exit code
 */
function command(Config $config, ?string $version = null, ?string $url = null): array
{
    // the CLI runs as the operator: a file:// archive is trusted as it is
    $payload = perform_update($config, $url, $version, allowLocalFile: true);

    return responder($payload);
}

/**
 * @return array{string, int} output and exit code
 */
function responder(Payload $payload): array
{
    if (!$payload->isSuccess) {
        $msg = is_array($payload->data) && isset($payload->data[0][1]) ? (string) $payload->data[0][1] : 'Update failed.';
        return [$msg . PHP_EOL, 1];
    }

    $d = $payload->data;

    if (!empty($d['upToDate'])) {
        return ['Already up to date (' . $d['from'] . ').' . PHP_EOL, 0];
    }

    return [
        'Updated ' . $d['from'] . ' -> ' . $d['to'] . PHP_EOL
        . 'Backup: ' . $d['backup'] . PHP_EOL,
        0,
    ];
}

<?php declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\core\config\Config;
use jaco\domain\Payload;

use function jaco\core\responder\cli_errors;
use function jaco\domain\system\authenticate_user;

/**
 * @return array{string, int} output and exit code, the caller prints and exits
 */
function command(Config $config, string $user, string $pass): array
{
    $payload = authenticate_user(
        $config,
        username: $user,
        withPassword: $pass,
    );

    return responder($payload, $user);
}

/**
 * @return array{string, int} output and exit code
 */
function responder(Payload $payload, string $user): array
{
    if ($payload->isSuccess) {
        if (isset($payload->data['totpRequired'])) {
            return ["Two-factor authentication is enabled, provide the code.\n", 1];
        }
        $output = sprintf('The token for user "%s" is:', $user) . PHP_EOL;
        $output .= $payload->data['token'] . PHP_EOL;
        return [$output, 0];
    }

    return cli_errors($payload->status, $payload->data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

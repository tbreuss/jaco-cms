<?php declare(strict_types=1);

namespace jaco\cli\auth\token;

use jaco\core\config\Config;
use jaco\domain\system\DomainPayload;

use function jaco\core\responder\cli_errors;
use function jaco\domain\system\authenticate_user;

function command(Config $config, string $user, string $pass): void
{
    $payload = authenticate_user(
        $config,
        username: $user,
        withPassword: $pass,
    );

    responder($payload, $user);
}

function responder(DomainPayload $payload, string $user): void
{
    if ($payload->isSuccess) {
        fwrite(STDOUT, sprintf('The token for user "%s" is:', $user) . PHP_EOL);
        fwrite(STDOUT, $payload->data['token'] . PHP_EOL);
        exit(0);
    }

    cli_errors($payload->status, $payload->data, [
        'username' => 'user',
        'withPassword' => 'pass',
    ]);
}

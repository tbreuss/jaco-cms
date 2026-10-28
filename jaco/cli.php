<?php declare(strict_types=1);

// Command line only: in a web request this file would print version and log output to
// anyone asking for it. The guard sits before the autoloader, so nothing runs at all.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

// adhocore/cli looks up unknown commands with a null array offset, which
// is deprecated on PHP 8.5 (upstream stale since 1.9.4). Our own code is
// verified deprecation free by phpstan-deprecation-rules, and the same
// domain paths run unmasked through the api tests.
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'vendor/autoload.php';

use Ahc\Cli\Application;

use function jaco\core\config\create_default_config;

$settings = [];

if (is_readable(__DIR__ . '/../jaco-data/settings.php')) {
    $settings = require(__DIR__ . '/../jaco-data/settings.php');
}

$config = create_default_config(__DIR__, is_array($settings) ? $settings : []);

$app = new Application('Jaco CLI', $config->appVersion);

$app->command('sys:version', 'Show version info.')
    ->action(function () use ($config): void {
        [$output, $exitCode] = \jaco\cli\version\command($config);
        echo $output;
        exit($exitCode);
    });

$app->command('sys:log', 'Show log entries.')
    ->option('-s --size', 'Number of lines to show', 'intval', 100)
    ->action(function (int $size) use ($config): void {
        [$output, $exitCode] = \jaco\cli\system\log\command($config, $size);
        echo $output;
        exit($exitCode);
    });

$app->command('sys:check-update', 'Check for a newer release.')
    ->action(function () use ($config): void {
        [$output, $exitCode] = \jaco\cli\system\check_update\command($config);
        echo $output;
        exit($exitCode);
    });

$app->command('sys:update', 'Download and install the latest release.')
    ->option('-t --target', 'Target version tag (default: latest)', 'strval', '')
    ->option('-u --url', 'Direct download URL (default: latest release)', 'strval', '')
    ->action(function (string $target, string $url) use ($config): void {
        [$output, $exitCode] = \jaco\cli\system\update\command(
            $config,
            $target !== '' ? $target : null,
            $url !== '' ? $url : null,
        );
        echo $output;
        exit($exitCode);
    });

$app->command('auth:token', 'Get token for credential.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->option('-p --pass', 'The Password', 'strval', '')
    ->option('-c --code', 'The two-factor code', 'strval', '')
    ->action(function (string $user, string $pass, string $code) use ($config): void {
        [$output, $exitCode] = \jaco\cli\auth\token\command($config, $user, $pass, $code);
        echo $output;
        exit($exitCode);
    });

$app->command('auth:password', 'Generate a password and its settings.php hash.')
    ->option('-a --ask', 'Ask for the password instead of generating one.', 'boolval', false)
    ->action(function (bool $ask) use ($app): void {
        $password = null;

        if ($ask) {
            /** @var \Ahc\Cli\IO\Interactor $io */
            $io = $app->io();
            $password = (string) $io->promptHidden('Password: ');
            $repeat = (string) $io->promptHidden('Repeat password: ');

            if ($password === '' || $password !== $repeat) {
                echo "Passwords are empty or do not match.\n";
                exit(1);
            }
        }

        [$output, $exitCode] = \jaco\cli\auth\password\command($password);
        echo $output;
        exit($exitCode);
    });

$app->command('auth:unblock', 'Clear the login blocks of all addresses.')
    ->action(function () use ($config): void {
        [$output, $exitCode] = \jaco\cli\auth\unblock\command($config);
        echo $output;
        exit($exitCode);
    });

$app->command('auth:totp-setup', 'Require two-factor authentication for one user.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->action(function (string $user) use ($config): void {
        [$output, $exitCode] = \jaco\cli\auth\totp\setup_command($config, $user);
        echo $output;
        exit($exitCode);
    });

$app->command('auth:totp-disable', 'Remove two-factor authentication for one user.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->action(function (string $user) use ($config): void {
        [$output, $exitCode] = \jaco\cli\auth\totp\disable_command($config, $user);
        echo $output;
        exit($exitCode);
    });

$app->onException(function (Throwable $e, int $exitCode) {
    echo $e->getMessage();
    exit($exitCode);
});

// $argv is only registered by PHP when the "register_argc_argv" ini setting is enabled,
// so read the arguments from $_SERVER to stay independent of the environment
/** @var list<string> $argv */
$argv = $_SERVER['argv'] ?? [];

$app->handle($argv);

exit(0);

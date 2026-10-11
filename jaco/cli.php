<?php declare(strict_types=1);

require_once 'vendor/autoload.php';

use Ahc\Cli\Application;

use function jaco\core\config\default_config;

$settings = [];

if (is_readable(__DIR__ . '/settings.php')) {
    $settings = require(__DIR__ . '/settings.php');
}

$config = default_config(__DIR__, is_array($settings) ? $settings : []);

$app = new Application('Jaco CLI', $config->appVersion);

$app->command('sys:version', 'Show version info.')
    ->action(function () use ($config): void {
        \jaco\cli\version\command($config);
    });

$app->command('sys:log', 'Show log entries.')
    ->option('-s --size', 'Number of lines to show', 'intval', 100)
    ->action(function (int $size) use ($config): void {
        \jaco\cli\system\log\command($config, $size);
    });

$app->command('auth:token', 'Get token for credential.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->option('-p --pass', 'The Password', 'strval', '')
    ->action(function (string $user, string $pass) use ($config): void {
        \jaco\cli\auth\token\command($config, $user, $pass);
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

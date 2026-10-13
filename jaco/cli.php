<?php declare(strict_types=1);

// adhocore/cli looks up unknown commands with a null array offset, which
// is deprecated on PHP 8.5 (upstream stale since 1.9.4). Our own code is
// verified deprecation free by phpstan-deprecation-rules, and the same
// domain paths run unmasked through the api tests.
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'vendor/autoload.php';

use Ahc\Cli\Application;

use function jaco\core\config\create_default_config;

$settings = [];

if (is_readable(__DIR__ . '/settings.php')) {
    $settings = require(__DIR__ . '/settings.php');
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

$app->command('auth:token', 'Get token for credential.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->option('-p --pass', 'The Password', 'strval', '')
    ->action(function (string $user, string $pass) use ($config): void {
        [$output, $exitCode] = \jaco\cli\auth\token\command($config, $user, $pass);
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

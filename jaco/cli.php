<?php declare(strict_types=1);

require_once 'vendor/autoload.php';

require('constants.php');

use Ahc\Cli\Application;

use const jaco\APP_VERSION;

$app = new Application('Jaco CLI', APP_VERSION);

$app->command('sys:version', 'Show version info.')
    ->action('jaco\cli\version\command');

$app->command('sys:log', 'Show log entries.')
    ->option('-s --size', 'Number of lines to show', 'intval', 100)
    ->action('jaco\cli\system\log\command');

$app->command('auth:token', 'Get token for credential.')
    ->option('-u --user', 'The Username', 'strval', '')
    ->option('-p --pass', 'The Password', 'strval', '')
    ->action('jaco\cli\auth\token\command');

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

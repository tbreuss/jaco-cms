<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

require('constants.php');

use Garden\Cli\Cli;

$cli = new Cli();

$cli->command('version')
    ->description('Show version info.');

$cli->command('auth:token')
    ->description('Get token for credentials.')
    ->opt('user:u', 'The Username.', true)
    ->opt('pass:p', 'The Password.', true);

$cli->command('push')
    ->description('Pull data from a remote server.')
    ->opt('host:h', 'Connect to host.', true)
    ->opt('port:P', 'Port number to use.', false, 'integer')
    ->opt('user:u', 'User for login if not current user.', true)
    ->opt('password:p', 'Password to use when connecting to server.')
    ->opt('database:d', 'The name of the database to dump.', true);

$cli->command('pull')
    ->description('Pull data from a remote server.')
    ->opt('host:h', 'Connect to host.', true)
    ->opt('port:P', 'Port number to use.', false, 'integer')
    ->opt('user:u', 'User for login if not current user.', true);


// Parse and return cli args.
$args = $cli->parse($argv);

$callback = sprintf('jaco\cli\%s\command', str_replace(':', '\\', $args->getCommand()));

if (is_callable($callback)) {
    $callback($args->getOpts(), $args->getArgs());
} else {
    fwrite(STDOUT, sprintf('Command %s not found.', $args->getCommand()) . PHP_EOL);
}

exit(0);

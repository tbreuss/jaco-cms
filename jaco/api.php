<?php declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\core\http;
use jaco\core\http\Request;
use jaco\core\router;

use function jaco\core\config\create_default_config;

set_error_handler('jaco\core\error\error_handler');
set_exception_handler('jaco\core\error\exception_handler');
register_shutdown_function('jaco\core\error\fatal_handler');

$settings = [];

if (is_readable('settings.php')) {
    $settings = require('settings.php');
}

$config = create_default_config(__DIR__, is_array($settings) ? $settings : []);

ini_set('error_reporting', (string) $config->phpErrorReporting);
ini_set('display_errors', (string) $config->phpDisplayErrors);
ini_set('display_startup_errors', (string) $config->phpDisplayStartupErrors);
ini_set('log_errors', (string) $config->phpLogErrors);
ini_set('error_log', $config->phpErrorLog);

require('routes.php');

$request = Request::fromGlobals();

$response = router\dispatch($request, $config);

http\emit($response);

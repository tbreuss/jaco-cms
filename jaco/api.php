<?php declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\core\http;
use jaco\core\http\Request;
use jaco\core\router;

set_error_handler('jaco\core\error\error_handler');
set_exception_handler('jaco\core\error\exception_handler');
register_shutdown_function('jaco\core\error\fatal_handler');

require('constants.php');

ini_set('error_reporting', (string) jaco\PHP_ERROR_REPORTING);
ini_set('display_errors', (string) jaco\PHP_DISPLAY_ERRORS);
ini_set('display_startup_errors', (string) jaco\PHP_DISPLAY_STARTUP_ERRORS);
ini_set('log_errors', (string) jaco\PHP_LOG_ERRORS);
ini_set('error_log', (string) jaco\PHP_ERROR_LOG);

require('routes.php');

$request = Request::fromGlobals();

$response = router\dispatch($request);

http\emit($response);

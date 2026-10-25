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

// Tests can point the API at a disposable settings file without touching the
// real jaco-data/settings.php. An explicitly set but unreadable path fails closed
// instead of silently falling back to the live configuration.
$settingsFile = getenv('JACO_SETTINGS_FILE');
if (is_string($settingsFile) && $settingsFile !== '') {
    $settingsFile = preg_match('#^(/|[A-Za-z]:[\\\\/])#', $settingsFile)
        ? $settingsFile
        : __DIR__ . '/' . $settingsFile;
    if (is_readable($settingsFile)) {
        $settings = require $settingsFile;
    }
} elseif (is_readable(__DIR__ . '/../jaco-data/settings.php')) {
    $settings = require __DIR__ . '/../jaco-data/settings.php';
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
// Let the frontend learn the maintenance state without a dedicated endpoint:
// every response carries it, so the banner can follow a toggled setting
// without a reload. The frontend ignores the header when it is absent.
$response->headers['X-Maintenance-Mode'] = $config->maintenanceMode ? 'true' : 'false';

http\emit($response);

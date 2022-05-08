<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\emitter;
use jaco\router;

// error handlers
set_error_handler('jaco\\exception\\error_handler');
set_exception_handler('jaco\\exception\\exception_handler');
register_shutdown_function('jaco\\exception\\fatal_handler');

// php stuff
ini_set('error_reporting', (string)E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/runtime/log/error.log');

// load settings
$settings = require('settings.php');

// set constants
define('JACO_VERSION', '0.1.0');
define('JACO_DIR', __DIR__);
define('JACO_DIR_NAME', basename(JACO_DIR));
define('JACO_BACKUPS_DIR', __DIR__ . '/_backups');
define('JACO_TEMPLATES_PAGES_DIR', __DIR__ . '/_templates/pages');
define('JACO_TEMPLATES_CONTENTS_DIR', __DIR__ . '/_templates/contents');
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_PERMISSIONS_DIRECTORIES', 0755);
define('JACO_PERMISSIONS_FILES', 0644);
define('JACO_SECRET_KEY', $settings['JACO_SECRET_KEY']);
define('JACO_USERNAME', $settings['JACO_USERNAME']);
define('JACO_PASSWORD', $settings['JACO_PASSWORD']);

// define public routes
router\get('index', 'jaco\\actions\\index\\main', false);
router\post('login', 'jaco\\actions\\login\\main', false);
router\get('translations', 'jaco\\actions\\translations\\main', false);

// define private routes
router\get('backup/list', 'jaco\\actions\\backup\\list\\main', true);
router\post('backup/restore', 'jaco\\actions\\backup\\restore\\main', true);
router\post('backup/delete', 'jaco\\actions\\backup\\delete\\main', true);
router\get('page/list', 'jaco\\actions\\page\\list\\main', true);
router\post('page/copy', 'jaco\\actions\\page\\copy\\main', true);
router\post('page/create', 'jaco\\actions\\page\\create\\main', true);
router\post('page/delete', 'jaco\\actions\\page\\delete\\main', true);
router\post('page/update', 'jaco\\actions\\page\\update\\main', true);
router\get('system', 'jaco\\actions\\system\\main', true);
router\get('template/pages', 'jaco\\actions\\template\\pages\\main', true);

// dispatch route
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$action = $_GET['action'] ?? 'index';
[$status, $response] = router\dispatch($method, $action);

// emit response
emitter\emit($status, $response);

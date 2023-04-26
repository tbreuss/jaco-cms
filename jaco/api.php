<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\http\emitter;
use jaco\router;

set_error_handler('jaco\error\error_handler');
set_exception_handler('jaco\error\exception_handler');
register_shutdown_function('jaco\error\fatal_handler');

require('constants.php');

ini_set('error_reporting', (string)E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', jaco\ERROR_LOG);

// public routes
router\get('index', 'jaco\api\index\action', false);
router\post('login', 'jaco\api\login\action', false);
router\get('translations', 'jaco\api\translation\action', false);

// private routes
router\get('backup/list', 'jaco\api\backup\list\action', true);
router\get('backup/view', 'jaco\api\backup\view\action', true);
router\post('backup/restore', 'jaco\api\backup\restore\action', true);
router\post('backup/delete', 'jaco\api\backup\delete\action', true);
router\get('page/list', 'jaco\api\page\list\action', true);
router\get('page/view', 'jaco\api\page\view\action', true);
router\post('page/copy', 'jaco\api\page\copy\action', true);
router\post('page/create', 'jaco\api\page\create\action', true);
router\post('page/delete', 'jaco\api\page\delete\action', true);
router\post('page/update', 'jaco\api\page\update\action', true);
router\get('system', 'jaco\api\system\action', true);
router\get('system/log', 'jaco\api\system\log\action', true);
router\get('system/php-info', 'jaco\api\system\phpinfo\action', true);
router\get('template/pages', 'jaco\api\template\pages\action', true);
router\get('upload/list', 'jaco\api\upload\list\action', true);
router\post('upload/delete', 'jaco\api\upload\delete\action', true);
#router\get('upload/view', 'jaco\api\upload\view\action', true);

[$status, $response] = router\dispatch(
    $_SERVER['REQUEST_METHOD'] ?? '',
    $_GET['action'] ?? 'index'
);

emitter\emit($status, $response);

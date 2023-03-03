<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\emitter;
use jaco\router;

// error handlers
set_error_handler('jaco\error\error_handler');
set_exception_handler('jaco\error\exception_handler');
register_shutdown_function('jaco\error\fatal_handler');

// php stuff
ini_set('error_reporting', (string)E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/runtime/log/error.log');

// load settings
$settings = require('settings.php');

require('constants.php');

// define public routes
router\get('index', 'jaco\action\index\main', false);
router\post('login', 'jaco\action\login\main', false);
router\get('translations', 'jaco\action\translation\main', false);

// define private routes
router\get('backup/list', 'jaco\action\backup\list\main', true);
router\get('backup/view', 'jaco\action\backup\view\main', true);
router\post('backup/restore', 'jaco\action\backup\restore\main', true);
router\post('backup/delete', 'jaco\action\backup\delete\main', true);
router\get('page/list', 'jaco\action\page\list\main', true);
router\post('page/copy', 'jaco\action\page\copy\main', true);
router\post('page/create', 'jaco\action\page\create\main', true);
router\post('page/delete', 'jaco\action\page\delete\main', true);
router\post('page/update', 'jaco\action\page\update\main', true);
router\get('system', 'jaco\action\system\main', true);
router\get('template/pages', 'jaco\action\template\pages\main', true);

// dispatch route
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$action = $_GET['action'] ?? 'index';
[$status, $response] = router\dispatch($method, $action);

// emit response
emitter\emit($status, $response);

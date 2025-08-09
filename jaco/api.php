<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\core\http\emitter;
use jaco\core\router;

set_error_handler('jaco\core\error\error_handler');
set_exception_handler('jaco\core\error\exception_handler');
register_shutdown_function('jaco\core\error\fatal_handler');

require('constants.php');

ini_set('error_reporting', (string)jaco\PHP_ERROR_REPORTING);
ini_set('display_errors', (string)jaco\PHP_DISPLAY_ERRORS);
ini_set('display_startup_errors', (string)jaco\PHP_DISPLAY_STARTUP_ERRORS);
ini_set('log_errors', (string)jaco\PHP_LOG_ERRORS);
ini_set('error_log', (string)jaco\PHP_ERROR_LOG);

// public routes

router\get(
    route: 'index',
    callback: 'jaco\api\index\action',
    authenticated: false,
    responseSchema: 'index/response.json'
);

router\post(
    route: 'login',
    callback: 'jaco\api\login\action',
    authenticated: false,
    requestSchema: 'login/request.json',
    responseSchema: 'login/response.json'
);

router\get(
    route: 'translations',
    callback: 'jaco\api\translation\action',
    authenticated: false,
    responseSchema: 'translation/response.json'
);

// private routes

router\post(
    route: 'backup/delete',
    callback: 'jaco\api\backup\delete\action',
    authenticated: true,
    requestSchema: 'backup/delete/request.json',
    responseSchema: 'null.json'
);

router\get(
    route: 'backup/list',
    callback: 'jaco\api\backup\list\action',
    authenticated: true,
    responseSchema: 'backup/list/response.json'
);

router\post(
    route: 'backup/restore',
    callback: 'jaco\api\backup\restore\action',
    authenticated: true,
    requestSchema: 'backup/restore/request.json',
    responseSchema: 'null.json'
);

router\get(
    route: 'backup/view',
    callback: 'jaco\api\backup\view\action',
    authenticated: true,
    responseSchema: 'backup/view/response.json'
);

router\post(
    route: 'page/copy',
    callback: 'jaco\api\page\copy\action',
    authenticated: true,
    requestSchema: 'page/copy/request.json',
    responseSchema: 'page/copy/response.json',
);

router\post(
    route: 'page/create',
    callback: 'jaco\api\page\create\action',
    authenticated: true,
    requestSchema: 'page/create/request.json',
    responseSchema: 'null.json'
);

router\post(
    route: 'page/delete',
    callback: 'jaco\api\page\delete\action',
    authenticated: true,
    requestSchema: 'page/delete/request.json',
    responseSchema: 'null.json'
);

router\get(
    route: 'page/list',
    callback: 'jaco\api\page\list\action',
    authenticated: true,
    responseSchema: 'page/list/response.json'
);

router\post(
    route: 'page/update',
    callback: 'jaco\api\page\update\action',
    authenticated: true,
    requestSchema: 'page/update/request.json',
    responseSchema: 'null.json'
);

router\get(
    route: 'page/view',
    callback: 'jaco\api\page\view\action',
    authenticated: true,
    responseSchema: 'page/view/response.json'
);

router\get(
    route: 'system',
    callback: 'jaco\api\system\action',
    authenticated: true,
    responseSchema: 'system/response.json'
);

router\get(
    route: 'system/log',
    callback: 'jaco\api\system\log\action',
    authenticated: true,
    responseSchema: 'diagnostics/log/response.json'
);

router\get(
    route: 'system/php-info',
    callback: 'jaco\api\system\phpinfo\action',
    authenticated: true,
    responseSchema: 'diagnostics/phpinfo/response.json'
);

router\get(
    route: 'template/pages',
    callback: 'jaco\api\template\pages\action',
    authenticated: true,
    responseSchema: 'template/response.json'
);

router\post(
    route: 'upload/delete',
    callback: 'jaco\api\upload\delete\action',
    authenticated: true,
    requestSchema: 'upload/delete/request.json',
    responseSchema: 'null.json'
);

router\get(
    route: 'upload/list',
    callback: 'jaco\api\upload\list\action',
    authenticated: true,
    responseSchema: 'upload/list/response.json'
);

/*router\get(
    route: 'upload/view',
    callback: 'jaco\api\upload\view\action',
    authenticated: true
);*/

$response = router\dispatch(
    $_SERVER['REQUEST_METHOD'] ?? '',
    $_GET['action'] ?? 'index'
);

emitter\emit($response);

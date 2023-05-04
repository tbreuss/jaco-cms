<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use jaco\http\emitter;
use jaco\router;

set_error_handler('jaco\error\error_handler');
set_exception_handler('jaco\error\exception_handler');
register_shutdown_function('jaco\error\fatal_handler');

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
    responseSchema: 'index/response.json',
    authenticated: false
);

router\post(
    route: 'login',
    callback: 'jaco\api\login\action',
    requestSchema: 'login/request.json',
    responseSchema: 'login/response.json',
    authenticated: false
);

router\get(
    route: 'translations',
    callback: 'jaco\api\translation\action',
    responseSchema: 'translation/response.json',
    authenticated: false
);

// private routes

router\post(
    route: 'backup/delete',
    callback: 'jaco\api\backup\delete\action',
    requestSchema: 'backup/delete/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\get(
    route: 'backup/list',
    callback: 'jaco\api\backup\list\action',
    responseSchema: 'backup/list/response.json',
    authenticated: true
);

router\post(
    route: 'backup/restore',
    callback: 'jaco\api\backup\restore\action',
    requestSchema: 'backup/restore/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\get(
    route: 'backup/view',
    callback: 'jaco\api\backup\view\action',
    responseSchema: 'backup/view/response.json',
    authenticated: true
);

router\post(
    route: 'page/copy',
    callback: 'jaco\api\page\copy\action',
    requestSchema: 'page/copy/request.json',
    responseSchema: 'page/copy/response.json',
    authenticated: true,
);

router\post(
    route: 'page/create',
    callback: 'jaco\api\page\create\action',
    requestSchema: 'page/create/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\post(
    route: 'page/delete',
    callback: 'jaco\api\page\delete\action',
    requestSchema: 'page/delete/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\get(
    route: 'page/list',
    callback: 'jaco\api\page\list\action',
    responseSchema: 'page/list/response.json',
    authenticated: true
);

router\post(
    route: 'page/update',
    callback: 'jaco\api\page\update\action',
    requestSchema: 'page/update/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\get(
    route: 'page/view',
    callback: 'jaco\api\page\view\action',
    responseSchema: 'page/view/response.json',
    authenticated: true
);

router\get(
    route: 'system',
    callback: 'jaco\api\system\action',
    responseSchema: 'system/response.json',
    authenticated: true
);

router\get(
    route: 'system/log',
    callback: 'jaco\api\system\log\action',
    responseSchema: 'diagnostics/log/response.json',
    authenticated: true
);

router\get(
    route: 'system/php-info',
    callback: 'jaco\api\system\phpinfo\action',
    responseSchema: 'diagnostics/phpinfo/response.json',
    authenticated: true
);

router\get(
    route: 'template/pages',
    callback: 'jaco\api\template\pages\action',
    responseSchema: 'template/response.json',
    authenticated: true
);

router\post(
    route: 'upload/delete',
    callback: 'jaco\api\upload\delete\action',
    requestSchema: 'upload/delete/request.json',
    responseSchema: 'null.json',
    authenticated: true
);

router\get(
    route: 'upload/list',
    callback: 'jaco\api\upload\list\action',
    responseSchema: 'upload/list/response.json',
    authenticated: true
);

/*router\get(
    route: 'upload/view',
    callback: 'jaco\api\upload\view\action',
    authenticated: true
);*/

[$status, $response] = router\dispatch(
    $_SERVER['REQUEST_METHOD'] ?? '',
    $_GET['action'] ?? 'index'
);

emitter\emit($status, $response);

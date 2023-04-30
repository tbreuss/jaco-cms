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

router\get(
    route: 'index',
    callback: 'jaco\api\index\action',
    requestSchema: null,
    responseSchema: '/api/index/response.json',
    authenticated: false
);

router\post(
    route: 'login',
    callback: 'jaco\api\login\action',
    requestSchema: '/api/login/request.json',
    responseSchema: '/api/login/response.json',
    authenticated: false
);

router\get(
    route: 'translations',
    callback: 'jaco\api\translation\action',
    responseSchema: '/api/translation/response.json',
    authenticated: false
);

// private routes

router\post(
    route: 'backup/delete',
    callback: 'jaco\api\backup\delete\action',
    requestSchema: '/api/backup/delete/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\get(
    route: 'backup/list',
    callback: 'jaco\api\backup\list\action',
    responseSchema: '/api/backup/list/response.json',
    authenticated: true
);

router\post(
    route: 'backup/restore',
    callback: 'jaco\api\backup\restore\action',
    requestSchema: '/api/backup/restore/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\get(
    route: 'backup/view',
    callback: 'jaco\api\backup\view\action',
    responseSchema: '/api/backup/view/response.json',
    authenticated: true
);

router\post(
    route: 'page/copy',
    callback: 'jaco\api\page\copy\action',
    requestSchema: '/api/page/copy/request.json',
    responseSchema: '/api/page/copy/response.json',
    authenticated: true,
);

router\post(
    route: 'page/create',
    callback: 'jaco\api\page\create\action',
    requestSchema: '/api/page/create/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\post(
    route: 'page/delete',
    callback: 'jaco\api\page\delete\action',
    requestSchema: '/api/page/delete/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\get(
    route: 'page/list',
    callback: 'jaco\api\page\list\action',
    responseSchema: '/api/page/list/response.json',
    authenticated: true
);

router\post(
    route: 'page/update',
    callback: 'jaco\api\page\update\action',
    requestSchema: '/api/page/update/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\get(
    route: 'page/view',
    callback: 'jaco\api\page\view\action',
    responseSchema: '/api/page/view/response.json',
    authenticated: true
);

router\get(
    route: 'system',
    callback: 'jaco\api\system\action',
    responseSchema: '/api/system/response.json',
    authenticated: true
);

router\get(
    route: 'system/log',
    callback: 'jaco\api\system\log\action',
    responseSchema: '/api/diagnostics/log/response.json',
    authenticated: true
);

router\get(
    route: 'system/php-info',
    callback: 'jaco\api\system\phpinfo\action',
    responseSchema: '/api/diagnostics/phpinfo/response.json',
    authenticated: true
);

router\get(
    route: 'template/pages',
    callback: 'jaco\api\template\pages\action',
    responseSchema: '/api/template/response.json',
    authenticated: true
);

router\post(
    route: 'upload/delete',
    callback: 'jaco\api\upload\delete\action',
    requestSchema: '/api/upload/delete/request.json',
    responseSchema: '/api/null.json',
    authenticated: true
);

router\get(
    route: 'upload/list',
    callback: 'jaco\api\upload\list\action',
    responseSchema: '/api/upload/list/response.json',
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

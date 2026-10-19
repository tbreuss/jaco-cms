<?php declare(strict_types=1);

use jaco\core\router;

// public routes

router\get(
    route: 'index',
    callback: jaco\api\index\action(...),
    authenticated: false,
    responseSchema: 'index/response.json',
);

router\post(
    route: 'login',
    callback: jaco\api\login\action(...),
    authenticated: false,
    requestSchema: 'login/request.json',
    responseSchema: 'login/response.json',
);

router\get(
    route: 'translations',
    callback: jaco\api\translation\action(...),
    authenticated: false,
    responseSchema: 'translation/response.json',
);

// private routes

router\post(
    route: 'backup/delete',
    callback: jaco\api\backup\delete\action(...),
    authenticated: true,
    role: 'admin',
    requestSchema: 'backup/delete/request.json',
    responseSchema: 'null.json',
);

router\get(
    route: 'backup/list',
    callback: jaco\api\backup\list\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'backup/list/response.json',
);

router\post(
    route: 'backup/restore',
    callback: jaco\api\backup\restore\action(...),
    authenticated: true,
    role: 'admin',
    requestSchema: 'backup/restore/request.json',
    responseSchema: 'null.json',
);

router\get(
    route: 'backup/view',
    callback: jaco\api\backup\view\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'backup/view/response.json',
);

router\post(
    route: 'page/copy',
    callback: jaco\api\page\copy\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'page/copy/request.json',
    responseSchema: 'page/copy/response.json',
);

router\post(
    route: 'page/create',
    callback: jaco\api\page\create\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'page/create/request.json',
    responseSchema: 'null.json',
);

router\post(
    route: 'page/delete',
    callback: jaco\api\page\delete\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'page/delete/request.json',
    responseSchema: 'null.json',
);

router\get(
    route: 'page/list',
    callback: jaco\api\page\list\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'page/list/response.json',
);

router\post(
    route: 'page/update',
    callback: jaco\api\page\update\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'page/update/request.json',
    responseSchema: 'null.json',
);

router\post(
    route: 'page/update-body',
    callback: jaco\api\page\update_body\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'page/update-body/request.json',
    responseSchema: 'null.json',
);

router\get(
    route: 'page/view',
    callback: jaco\api\page\view\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'page/view/response.json',
);

router\get(
    route: 'system',
    callback: jaco\api\system\action(...),
    authenticated: true,
    role: 'admin',
    responseSchema: 'system/response.json',
);

router\get(
    route: 'system/log',
    callback: jaco\api\system\log\action(...),
    authenticated: true,
    role: 'admin',
    responseSchema: 'diagnostics/log/response.json',
);

router\post(
    route: 'system/log/clear',
    callback: jaco\api\system\log\clear\action(...),
    authenticated: true,
    role: 'admin',
    responseSchema: 'null.json',
);

router\post(
    route: 'system/change-password',
    callback: jaco\api\system\change_password\action(...),
    authenticated: true,
    role: 'viewer',
    requestSchema: 'system/change-password/request.json',
    responseSchema: 'null.json',
);

router\get(
    route: 'system/php-info',
    callback: jaco\api\system\phpinfo\action(...),
    authenticated: true,
    role: 'admin',
    responseSchema: 'diagnostics/phpinfo/response.json',
);

router\get(
    route: 'template/pages',
    callback: jaco\api\template\pages\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'template/response.json',
);

router\get(
    route: 'template/content',
    callback: jaco\api\template\content\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'template/response.json',
);

router\get(
    route: 'template/content/view',
    callback: jaco\api\template\content\view\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'template/content/view/response.json',
);

router\get(
    route: 'css/view',
    callback: jaco\api\css\view\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'css/view/response.json',
);

router\get(
    route: 'css/list',
    callback: jaco\api\css\files\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'css/list/response.json',
);

router\post(
    route: 'css/update',
    callback: jaco\api\css\update\action(...),
    authenticated: true,
    role: 'admin',
    requestSchema: 'css/update/request.json',
    responseSchema: 'null.json',
);

router\post(
    route: 'upload/delete',
    callback: jaco\api\upload\delete\action(...),
    authenticated: true,
    role: 'editor',
    requestSchema: 'upload/delete/request.json',
    responseSchema: 'null.json',
);

router\post(
    route: 'upload/create',
    callback: jaco\api\upload\create\action(...),
    authenticated: true,
    role: 'editor',
    responseSchema: 'upload/create/response.json',
);

router\get(
    route: 'upload/list',
    callback: jaco\api\upload\list\action(...),
    authenticated: true,
    role: 'viewer',
    responseSchema: 'upload/list/response.json',
);

/*router\get(
    route: 'upload/view',
    callback: jaco\api\upload\view\action(...),
    authenticated: true
);*/

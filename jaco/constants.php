<?php

declare(strict_types=1);

define('jaco\VERSION', '0.1.0');
define('jaco\JACO_DIR', __DIR__);
define('jaco\JACO_DIR_NAME', basename(jaco\JACO_DIR));
define('jaco\BACKUPS_DIR', __DIR__ . '/_backup');
define('jaco\TEMPLATES_PAGES_DIR', __DIR__ . '/_template/page');
define('jaco\TEMPLATES_CONTENTS_DIR', __DIR__ . '/_template/content');
define('jaco\WEB_DIR', dirname(__DIR__));
define('jaco\UPLOADS_DIR', dirname(__DIR__) . '/upload');
define('jaco\ALLOWED_EXTENSIONS', ['htm', 'html']);
define('jaco\DEFAULT_PAGE', 'index.html');
define('jaco\DEFAULT_LANGUAGE', 'en');
define('jaco\PERMISSIONS_DIRECTORIES', 0755);
define('jaco\PERMISSIONS_FILES', 0644);
define('jaco\SECRET_KEY', $settings['jaco\SECRET_KEY'] ?? openssl_random_pseudo_bytes(32));
define('jaco\USERNAME', $settings['jaco\USERNAME'] ?? 'admin');
define('jaco\PASSWORD', $settings['jaco\PASSWORD'] ?? openssl_random_pseudo_bytes(32));
define('jaco\HTTP_OK', 200);
define('jaco\HTTP_BAD_REQUEST', 400);
define('jaco\HTTP_FORBIDDEN', 403);
define('jaco\HTTP_NOT_FOUND', 404);
define('jaco\HTTP_METHOD_NOT_ALLOWED', 405);
define('jaco\HTTP_UNPROCESSABLE_CONTENT', 422);
define('jaco\HTTP_INTERNAL_SERVER_ERROR', 500);

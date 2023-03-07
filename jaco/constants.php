<?php

declare(strict_types=1);

$settings = require('settings.php');

define('jaco\APP_VERSION', '0.1.0');
define('jaco\APP_DIR', __DIR__);
define('jaco\APP_DIR_NAME', basename(jaco\APP_DIR));
define('jaco\BACKUP_DIR', __DIR__ . '/_backup');
define('jaco\TEMPLATE_PAGE_DIR', __DIR__ . '/_template/page');
define('jaco\TEMPLATE_CONTENT_DIR', __DIR__ . '/_template/content');
define('jaco\WEB_DIR', dirname(__DIR__));
define('jaco\UPLOAD_DIR', dirname(__DIR__) . '/upload');
define('jaco\UPLOAD_EXTENSIONS', ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf']);
define('jaco\PAGE_EXTENSIONS', ['htm', 'html']);
define('jaco\DEFAULT_PAGE', 'index.html');
define('jaco\DEFAULT_LANGUAGE', 'en');
define('jaco\DIRECTORY_PERMISSIONS', 0755);
define('jaco\FILE_PERMISSIONS', 0644);
define('jaco\SECRET_KEY', $settings['jaco\SECRET_KEY'] ?? openssl_random_pseudo_bytes(32));
define('jaco\USERNAME', $settings['jaco\USERNAME'] ?? 'admin');
define('jaco\PASSWORD', $settings['jaco\PASSWORD'] ?? openssl_random_pseudo_bytes(32));
define('jaco\ERROR_LOG', __DIR__ . '/runtime/log/error.log');

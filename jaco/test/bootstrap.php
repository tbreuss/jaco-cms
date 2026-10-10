<?php declare(strict_types=1);

if (is_readable(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

define('jaco\APP_VERSION', '0.1.0');
define('jaco\APP_DIR', __DIR__ . '/asset/jaco');
define('jaco\APP_DIR_NAME', basename(jaco\APP_DIR));
define('jaco\BACKUP_DIR', jaco\APP_DIR . '/backup');
define('jaco\TEMPLATE_PAGE_DIR', jaco\APP_DIR . '/page');
define('jaco\TEMPLATE_CONTENT_DIR', jaco\APP_DIR . '/content');
define('jaco\WEB_DIR', dirname(jaco\APP_DIR));
define('jaco\UPLOAD_DIR', dirname(jaco\APP_DIR) . '/upload');
define('jaco\UPLOAD_EXTENSIONS', ['png', 'gif', 'jpg', 'jpeg', 'svg', 'pdf']);
define('jaco\UPLOAD_URL', 'upload');
define('jaco\PAGE_EXTENSIONS', ['htm', 'html']);
define('jaco\DEFAULT_PAGE', 'index.html');
define('jaco\DEFAULT_LANGUAGE', 'en');
define('jaco\DIRECTORY_PERMISSIONS', 0755);
define('jaco\FILE_PERMISSIONS', 0644);
define('jaco\SECRET_KEY', 'test_1234');
define('jaco\USERNAME', 'test_user');
define('jaco\PASSWORD', 'test_pass');
define('jaco\JSON_SCHEMA_URL', 'https://jaco.tebe.ch');
define('jaco\PHP_ERROR_REPORTING', E_ALL);
define('jaco\PHP_DISPLAY_ERRORS', 0);
define('jaco\PHP_DISPLAY_STARTUP_ERRORS', 0);
define('jaco\PHP_LOG_ERRORS', 1);
define('jaco\PHP_ERROR_LOG', __DIR__ . '/runtime/log/error.log');

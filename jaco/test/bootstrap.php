<?php

define('JACO_VERSION', '0.1.0');
define('APP_DIR', __DIR__ . '/asset/jaco');
define('APP_DIR_NAME', basename(APP_DIR));
define('JACO_BACKUPS_DIR', APP_DIR . '/backup');
define('JACO_TEMPLATES_PAGES_DIR', APP_DIR . '/page');
define('JACO_TEMPLATES_CONTENTS_DIR', APP_DIR . '/content');
define('JACO_WEB_DIR', dirname(APP_DIR));
define('JACO_UPLOAD_DIR', dirname(APP_DIR) . '/upload');
define('JACO_PAGE_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_DIRECTORY_PERMISSIONS', 0755);
define('JACO_FILE_PERMISSIONS', 644);
define('JACO_SECRET_KEY', 'test_1234');
define('JACO_USERNAME', 'test_user');
define('JACO_PASSWORD', 'test_pass');

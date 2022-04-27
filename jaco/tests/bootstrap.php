<?php

define('JACO_VERSION', '0.1.0');
define('JACO_DIR', __DIR__ . '/assets/jaco');
define('JACO_DIR_NAME', basename(JACO_DIR));
define('JACO_BACKUPS_DIR', JACO_DIR . '/backups');
define('JACO_TEMPLATES_PAGES_DIR', JACO_DIR . '/pages');
define('JACO_TEMPLATES_CONTENTS_DIR', JACO_DIR . '/contents');
define('JACO_WEB_DIR', dirname(JACO_DIR));
define('JACO_UPLOADS_DIR', dirname(JACO_DIR) . '/uploads');
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_PERMISSIONS_DIRECTORIES', 0755);
define('JACO_PERMISSIONS_FILES', 644);
define('JACO_SECRET_KEY', 'test_1234');
define('JACO_USERNAME', 'test_user');
define('JACO_PASSWORD', 'test_pass');

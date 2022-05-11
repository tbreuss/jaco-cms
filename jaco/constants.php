<?php

declare(strict_types=1);

// set constants
define('JACO_VERSION', '0.1.0');
define('JACO_DIR', __DIR__);
define('JACO_DIR_NAME', basename(JACO_DIR));
define('JACO_BACKUPS_DIR', __DIR__ . '/_backup');
define('JACO_TEMPLATES_PAGES_DIR', __DIR__ . '/_template/page');
define('JACO_TEMPLATES_CONTENTS_DIR', __DIR__ . '/_template/content');
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_UPLOADS_DIR', dirname(__DIR__) . '/upload');
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_PERMISSIONS_DIRECTORIES', 0755);
define('JACO_PERMISSIONS_FILES', 0644);
define('JACO_SECRET_KEY', $settings['JACO_SECRET_KEY'] ?? openssl_random_pseudo_bytes(32));
define('JACO_USERNAME', $settings['JACO_USERNAME'] ?? 'admin');
define('JACO_PASSWORD', $settings['JACO_PASSWORD'] ?? openssl_random_pseudo_bytes(32));

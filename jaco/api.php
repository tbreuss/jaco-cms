<?php

require 'vendor/autoload.php';

use jaco\router;
use jaco\emitter;

$settings = require 'settings.php';

// set constants
define('JACO_VERSION', '0.1.0');
define('JACO_CMS_DIR', __DIR__);
define('JACO_BACKUPS_DIR', __DIR__ . '/_backups');
define('JACO_TEMPLATES_PAGES_DIR', __DIR__ . '/_templates/pages');
define('JACO_TEMPLATES_CONTENTS_DIR', __DIR__ . '/_templates/contents');
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_SECRET_KEY', $settings['JACO_SECRET_KEY']);
define('JACO_USERNAME', $settings['JACO_USERNAME']);
define('JACO_PASSWORD', $settings['JACO_PASSWORD']);

// define public routes
router\get('index', 'jaco\\actions\\index\\main', false);
router\post('login', 'jaco\\actions\\login\\main', false);
router\get('translations', 'jaco\\actions\\translations\\main', false);

// define private routes
router\post('page/copy', 'jaco\\actions\\page\\copy\\main', true);
router\post('page/create', 'jaco\\actions\\page\\create\\main', true);
router\post('page/delete', 'jaco\\actions\\page\\delete\\main', true);
router\post('page/update', 'jaco\\actions\\page\\update\\main', true);

// dispatch route
[$status, $response] = router\dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_GET['action'] ?? 'index'
);

// emit response
emitter\emit($status, $response);

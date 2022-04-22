<?php

require 'vendor/autoload.php';

use jaco\route;

$settings = require 'settings.php';

define('JACO_CMS_DIR', __DIR__);
define('JACO_BACKUPS_DIR', dirname(__DIR__) . '/backups');
define('JACO_UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');
define('JACO_JWT_KEY', 'z2MSdMsCEs8eQpZV');
define('JACO_USERNAME', $settings['JACO_USERNAME']);
define('JACO_PASSWORD', $settings['JACO_PASSWORD']);

try {

    $action = route\get_action();
    $response = route\call_action($action);

} catch (Throwable $e) {

    $action = route\get_error_action();
    $response = $action($e);

    http_response_code($e->getCode());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;

}

if (is_string($response)) {
    echo $response;
} elseif (is_array($response)) {
    echo json_encode($response);
}

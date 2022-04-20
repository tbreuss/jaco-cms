<?php

require 'vendor/autoload.php';

use jaco\exception;
use jaco\route;

define('JACO_CMS_DIR', __DIR__);
define('JACO_BACKUPS_DIR', dirname(__DIR__) . '/backups');
define('JACO_UPLOADS_DIR', dirname(__DIR__) . '/uploads');
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');
define('JACO_DEFAULT_LANGUAGE', 'en');

try {

    $action = route\get_action();
    $response = route\call_action($action);

} catch (exception\Exception $e) {

    $action = route\get_error_action();
    $response = $action($e);

}

if (is_string($response)) {
    echo $response;
} elseif (is_array($response)) {
    echo json_encode($response);    
}

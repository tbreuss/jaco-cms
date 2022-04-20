<?php

require 'vendor/autoload.php';

use jaco\exception;
use jaco\route;

define('JACO_CMS_DIR', __DIR__);
define('JACO_WEB_DIR', dirname(__DIR__));
define('JACO_ALLOWED_EXTENSIONS', ['htm', 'html']);
define('JACO_DEFAULT_PAGE', 'index.html');

try {

    $action = route\get_action();
    $response = route\call_action($action);

} catch (exception\Exception $e) {

    $action = route\get_error_action();
    $response = $action($e);

}

echo $response;

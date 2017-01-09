<?php

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/admin/libs'),
    get_include_path(), //uncomment for developer environment only
)));

require_once 'admin/includes/bootstrap.php';

$controllerName = 'HandlerController';
require ROOT_DIR . 'controllers' . DIRECTORY_SEPARATOR . $controllerName . '.php';

if (!empty($_REQUEST['act'])) {
    $actionName = trim($_REQUEST['act']);
} else {
    $actionName = 'index';
}

$actionMethod = $actionName.'Action';

if (method_exists($controllerName, $actionMethod)) {
    $controler = new $controllerName;
    call_user_func(array($controler, $actionMethod));
} else {
    echo 'action not found';
    exit;
}
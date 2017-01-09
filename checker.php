<?php

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/admin/libs'),
    get_include_path(), //uncomment for developer environment only
)));

require_once 'admin/includes/bootstrap.php';

$controllerName = 'HandlerController';
$actionName = 'sitechecker';

require ROOT_DIR . 'controllers' . DIRECTORY_SEPARATOR . $controllerName . '.php';

$actionMethod = $actionName.'Action';

if (method_exists($controllerName, $actionMethod)) {
    $controller = new $controllerName($controllerName);
    call_user_func(array($controller, $actionMethod));
} else {
    echo 'action not found';
    exit;
}
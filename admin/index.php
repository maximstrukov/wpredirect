<?php

//echo "<pre>";
//print_r($_SERVER);
//die();

error_reporting(E_ALL);
ini_set("display_errors", 1);

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/libs'),
    get_include_path(), //uncomment for developer environment only
)));

require_once 'includes/bootstrap.php';

dispatcher();


function dispatcher() {
    $controllersDir = '.' . DIRECTORY_SEPARATOR . 'controllers';

    if (!empty($_REQUEST['cont'])) {
        $controllerName = trim($_REQUEST['cont']);
    } else {
        $controllerName = 'campaign';
    }

    $controllerName = ucwords($controllerName);
    $controllerName .= 'Controller';

    $controllerFile = $controllersDir . DIRECTORY_SEPARATOR . $controllerName . '.php';
    // die($controllerFile);
    if (file_exists($controllerFile)) {
        require $controllerFile;

        if (!empty($_REQUEST['act'])) {
            $actionName = trim($_REQUEST['act']);
        } else {
            $actionName = 'index';
        }
        $actionName = ucwords($actionName);
        $actionName = $actionName . 'Action';

        if (method_exists($controllerName, $actionName)) {
            $controler = new $controllerName;
            call_user_func(array($controler, $actionName));
        } else {
            die('action not found');
        }
    } else {
        die('controller not found');
    }
}
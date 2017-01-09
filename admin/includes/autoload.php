<?php

function ClassLoader($class) {
    if (strpos($class, 'Controller') !== false) {
        if (file_exists(ROOT_DIR . 'controllers'. DIRECTORY_SEPARATOR.$class . '.php')) {
            require_once(ROOT_DIR . 'controllers'.DIRECTORY_SEPARATOR.$class . '.php');
            return true;
        } 
    } else if (file_exists(ROOT_DIR . 'models'. DIRECTORY_SEPARATOR.$class . '.php')) {
        require_once(ROOT_DIR . 'models'.DIRECTORY_SEPARATOR.$class . '.php');
        return true;
    }else if (file_exists(ROOT_DIR . 'libs'. DIRECTORY_SEPARATOR.$class . '.php')) {
        require_once(ROOT_DIR . 'libs'.DIRECTORY_SEPARATOR.$class . '.php');
        return true;
    }
    return false;
}

spl_autoload_register('ClassLoader');
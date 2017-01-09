<?php

/**
 * Application container
 */

class App {
    private static $instance = null;
    public $app = array();
    
    private function __construct() {
    }
    
    public static function inst(){
        if (is_null(self::$instance))
            self::$instance = new self;
        
        return self::$instance;
    }
    
    public function __get($name) {
        return isset($this->app[$name]) ? $this->app[$name] : NULL;
    }
    
    public function __set($name, $value){
        $this->app[$name] = $value;
    }
    
    public static function escape($html_escape) {
        $html_escape = htmlspecialchars($html_escape, ENT_QUOTES | 'ENT_HTML5', 'UTF-8');
        return $html_escape;
    }    
    
    public static function dump($var) {
        echo "<pre>";
        var_dump($var);
        echo "</pre>";
    }
    
}
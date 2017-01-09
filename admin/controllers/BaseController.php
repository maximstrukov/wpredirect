<?php

//require_once 'AController.php';

class BaseController extends AController{
    protected $layout = 'main';
    
    function __construct( $controllerName = false ) {
        
        // check login here 
        /*if(!Auth::hasIdentity() && $controllerName !='HandlerController') 
            header('Location: /index.php?cont=login');*/
        
        // ERRORS BLOCK 
        Err::getCCError();
    }    
    
    public function getTimeOptions() {
        $time_options = array();

        for ($h = 0; $h < 24; $h++) {
            for ($m = 0; $m < 60; $m+=20)
                $time_options[] = ($h < 10 ? '0' : '') . $h . ':' . ($m < 10 ? '0' : '') . $m;
        }

        return $time_options;
    }

    public function getCountryCodes() {
        $response = app::inst()->db->query("select * from urls_iptable GROUP by country_code")->fetchAll();
        return $response;
    }    
    
//    public function isValidURL($url) {
//        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
//    }
    
    public function isValidURL($url) 
    {     
        if(!filter_var($url, FILTER_VALIDATE_URL)) 
                return false; 
        else return true;
    }
}
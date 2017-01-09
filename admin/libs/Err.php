<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Err
 *
 * @author dmitry
 */
class Err {
    
    /**
     * getCCErrors
     * @desc get curlChecker error
     */ 
    public static function getCCError() {
        
        $siteModel = new Site(); 
        $sData = $siteModel->getDataByFields(array(), true, array(), array('domain','curl_status'));
        
        if(!isset($_SESSION['gError']['CurlChecker']) || 
                (isset($_SESSION['gError']['CurlChecker']['switchon']) 
                    && $_SESSION['gError']['CurlChecker']['switchon'] != false)) {
            
            $errSites = array();
            
            foreach($sData as $cuData) {
                if($cuData['curl_status']==0)     
                    $errSites[] = $cuData['domain']; 
            }
            
            if(!empty($errSites)) {
                    
                    $siteList = "<br />Sites with errors: http://".implode(', http://', $errSites).' .'; 
                    $swithon = isset($_SESSION['gError']['CurlChecker']['switchon']) ? $_SESSION['gError']['CurlChecker']['switchon'] : true;
                    $_SESSION['gError']['CurlChecker'] = array(
                            'message'=> ' some sites has CURL problems (no outgoing connections allowable from them). Please go to Sites tab and investigate.'.$siteList,
                            'switchon'=> $swithon
                        );                                 
            }
            else self::clearCCError();
        }
    }
    
    /**
     * clearCCError
     * @desc clear curlChecker error
     */     
    public static function clearCCError() { 
        unset($_SESSION['gError']['CurlChecker']);
    }

    /**
     * clearCCError
     * @desc hide curlChecker error
     */         
    public static function hideCCError() {
        $_SESSION['gError']['CurlChecker']['switchon'] = false;
    }  
}

?>

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RedirectAPI
 *
 * @author dmitry
 */

class RedirectAPI {
    
    /**
     *@desc Redisign library add __construct method, 
     *       The constructor is not used before. 
     *       For the future: Need refactoring all code.
     */
    
    protected $domain = '';
    protected $params = array(); 
    
    function __construct($site_id = null) {
        
        if(!is_null($site_id)) {
            
            $siteModel = new Site();
            $siteData = $siteModel->getSites($site_id);
            $this->domain = $siteData['domain'];
            $this->params = array(
                'login' => $siteData['wp_login'],
                'pass' => $siteData['wp_pass']
            ); 
        }
    }
    
    public function checkRemoteCurl() 
    {
        if($this->domain) {
            
            $redirectapiUrl = 'http://'.$this->domain.'/?redirectapi&act=checkcurl';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);            

            $response = curl_exec($ch);
            
            curl_close($ch);
        }
        
        return $response; 
    }
    
    /**
     * changeaAdminLogin
     * @desc  replaced old admin login by new on the remote mini-site
     * @param string $old_login
     * @param string $new_login
     * @return mixed
     */
    public function changeaAdminLogin($old_login, $new_login)
    {
        $response = false; 
        
        if($new_login && 
                $old_login) {
            
            $redirectapiUrl = 'http://'.$this->domain.'/?redirectapi&act=uplogin&old_login='.$old_login.'&new_login='.$new_login;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);            

            $response = curl_exec($ch);
            
            curl_close($ch);
        }
        
        return $response;
    }
    
    public function setPermaLink($site_id, $permalink_struct) 
    {
        $siteModel = new Site();
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=setpermalink';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass, 
            'permalink_struct' => $permalink_struct
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        
        
        $response = curl_exec($ch);
        //$cData = json_decode($response, true);
        curl_close($ch);         
        
        return !empty($response) ? $response : false;
    }
    
    public function getPermaLink($site_id) 
    {     
        $siteModel = new Site();
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=getpermalink';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        
        
        $response = curl_exec($ch);
        $cData = json_decode($response, true);
        curl_close($ch);    
        
        return isset($cData['permalink_structure']) ? $cData['permalink_structure'] : false;
    }
    
    public function getPostIDs($domain, $wp_login, $wp_pass) 
    {
        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=getposts';
        //die($redirectapiUrl); 
        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);     
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt'); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        
        $response = curl_exec($ch);
        
        curl_close($ch);    
        
        $result = json_decode($response, true);
        
        return $result;
    }
    
    public function setNavmenuCategory($site_id, $cat_id)
    {
        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=setnavmenu';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass,
            'cat_id' => $cat_id
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);     
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt'); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        
        $response = curl_exec($ch);
        //$cData = json_decode($res, true);
        curl_close($ch);    
        
        return $response;
    }

    public function getMagicPatam($site_id, $is_mp2 = false)
    {
        // get magic parameter 
        $magicParam = false; 

        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=getmagic';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass,
            'mp2' => $is_mp2
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $cData = json_decode($res, true);
        curl_close($ch);

        if(count($cData))
            if(isset($cData['magic']) && !empty($cData['magic']))
                $magicParam = $cData['magic'];

        return $magicParam; 
        // end get magic parameter
    }
    
    public function setMagicParam($site_id, $option_value, $is_mp2 = false)
    {
        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=setmagic';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass,
            'value' => $option_value,
            'mp2' => $is_mp2
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        //$cData = json_decode($res, true);
        curl_close($ch);    
        
        return $response;
    }
    
    public function setShowIFrame($site_id, $option_value)
    {
        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $redirectapiUrl = 'http://'.$domain.'/?redirectapi&act=setshowiframe';

        $params = array(
            'login' => $wp_login,
            'pass' => $wp_pass,
            'value' => $option_value,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $redirectapiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        //$cData = json_decode($res, true);
        curl_close($ch);    
        
        return $response;
    }    
    
}

?>

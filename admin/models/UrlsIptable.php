<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlsIptable
 *
 * @author dmitry
 */
class UrlsIptable extends BaseModel {
    
    public $id;
    public $start_ip;
    public $end_ip;
    public $country_code;
    public $country_name;
    public $isp_name;
    
    protected $table = 'urls_iptable';
    
    public function getUserISpByIP($ip, $country_code = false) 
    {
        $fData = array(); 
        
        if($country_code) {
            
            $sql = 'SELECT * FROM '.$this->table.'
                    WHERE country_code = :country_code AND :ip between start_ip AND end_ip';
            $fData = array(':ip' => $ip, ':country_code' => $country_code);
        } 
        else {
            
            $sql = 'SELECT * FROM '.$this->table.'
                    WHERE :ip between start_ip AND end_ip';
            
            $fData = array(':ip' => $ip); 
        }
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($fData);
        
        return $smtm->fetch();
    }
}

?>

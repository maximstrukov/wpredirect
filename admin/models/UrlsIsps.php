<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlsIsps
 *
 * @author dmitry
 */
class UrlsIsps extends BaseModel 
{
    public $IPnumbers; 
    public $isp_name;
    public $country_code;
    
    protected $table = 'urls_isps';
    
    public function searchIspDataByName( $isp_name, $country_code )
    {
        $sql = 'SELECT * FROM '.$this->table.' WHERE country_code = :country_code AND isp_name LIKE "%'.$isp_name.'%" ';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
           // ':isp_name' => '"%'.$isp_name.'%"',
            ':country_code' => $country_code
        ));
        $result = $smtm->fetchAll();
        return $result;        
    }
}

?>

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlsProvidersTmp
 *
 * @author dmitry
 */
class UrlsProvidersTmp extends BaseModel 
{
    public $urls_id;  
    public $provider_name;
    public $status;
    public $saved;   
    
    protected $table = 'urls_providers_tmp';
    
    /**
     * deleteBySearchData
     * @param int $urls_id
     * @param string $search
     * @return mixed
     */
    public function deleteBySearchData($urls_id, $search)
    {
        $result = false; 
        
        if($urls_id) {
            
            $filter = array(':urls_id' => $urls_id);
            
            $sql_tmp = 'DELETE FROM '.$this->table.' WHERE urls_id = :urls_id ';

            if (@trim($search) != '') {

                $sql_tmp .= ' and  provider_name LIKE "%'.$search.'%"';
            }

            $smtm = app::inst()->db->prepare($sql_tmp);
            
            $result = $smtm->execute($filter);
        }
        
        return $result; 
    }
    
    /**
     * selIpsByData
     * @desc selected array of IPS by get count and also country code with advertiser id (urls_id)
     * @param string $country_code
     * @param int $urls_id
     * @param int $count
     * @return array  
     */
    public function selIpsByData($country_code, $urls_id, $count)
    {
        $sql = "INSERT INTO ".$this->table." 
                SELECT 
                    :urls_id as urls_id, 
                    urls_isps.isp_name as provider_name, 
                    'added' as status,
                    0 as saved
                FROM `urls_isps` WHERE urls_isps.country_code = :country_code LIMIT ".$count; //:limit";
        
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(
            ':country_code' => trim(strtoupper($country_code)),
            //':limit'=>(int)$count,
            ':urls_id'=>(int)$urls_id
        ));        
    }
    
    public function getDataByUrlsID($urls_id)
    {
        $sql = 'SELECT * from `'.$this->table.'` where urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':urls_id' => $urls_id));
        return $smtm->fetchAll();
    }

    public function addData($urls_id, $provider_name, $status)
    {
        $sql = 'INSERT INTO '.$this->table.' SET
                urls_id = :urls_id,
                provider_name = :provider_name,
                status = :status
                ON DUPLICATE KEY UPDATE status = :status,
                    saved = if(saved=1,2,0)
            ';

        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(
                    ':urls_id' => $urls_id,
                    ':provider_name' => $provider_name, 
                    ':status' => $status
                ));
    }
    
    public function deleteByUrlsID($id)
    {
        $dData = array(':urls_id' => $id);  
        $sql = 'DELETE from `'.$this->table.'` WHERE `status` = "deleted" and urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute($dData);
    }
    
    public function deleteByUrlsIDForce($id)
    {
        $dData = array(':urls_id' => $id);  
        $sql = 'DELETE from `'.$this->table.'` WHERE urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute($dData);
    }    
    
    public function deleteByProviderNameAndUrlsID($id, $provider_name)
    {        
        $dData = array(
            ':urls_id' => $id,
            ':provider_name' => $provider_name
        );
        
        $sql = 'DELETE from `'.$this->table.'` WHERE 
                        urls_id = :urls_id AND 
                        provider_name = :provider_name';
        
        $smtm = app::inst()->db->prepare($sql);
        
        return $smtm->execute($dData);
    }
    
    public function addDataFromUrlsProviderTableByID($id)
    {
        $sql = 'INSERT INTO `'.$this->table.'`
                SELECT * from `urls_providers` where urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':urls_id' => $id));
    }    
    
    public function changeUrlsID($old_urls_id, $new_urls_id)
    {
        $sql = 'UPDATE `'.$this->table.'` SET urls_id = :new_urls_id
                WHERE urls_id = :old_urls_id';

        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':old_urls_id' => $old_urls_id,
            ':new_urls_id' => $new_urls_id
        ));        
    }

}

?>

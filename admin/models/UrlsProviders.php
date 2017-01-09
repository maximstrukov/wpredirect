<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlsProviders
 *
 * @author dmitry
 */
class UrlsProviders extends BaseModel 
{
    public $urls_id;  
    public $provider_name;
    public $status;
    public $saved;   
    
    protected $table = 'urls_providers';
    
    
    public function updateBySearchData($urls_id, $search)
    {
        $result = false;
        
        if($urls_id) {
            
            $filter = array(':urls_id' => $urls_id);

            $sql = 'UPDATE '.$this->table.' set status="deleted",
                saved = if(saved=1,2,0)
                where urls_id = :urls_id ';        

            if (@trim($search) != '') {

                $sql .= ' and  provider_name LIKE "%'.$search.'%"';
            }        

            $smtm = app::inst()->db->prepare($sql);
            
            $result = $smtm->execute($filter);
        }
        
        return $result;
    }
    
    public function addData($urls_id, $provider_name, $status)
    {
        $sql = 'INSERT INTO '.$this->table.' SET
                urls_id = :urls_id,
                provider_name = :provider_name,
                status = :status
                ON DUPLICATE KEY UPDATE status = :status,
                    saved = 1
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
        $sql = 'DELETE from `'.$this->table.'` WHERE urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':urls_id' => $id));
    }
    
    public function setSavedByUrlsID($id)
    {
        $sql = 'UPDATE `'.$this->table.'` SET `saved` = 1 where urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':urls_id' => $id));
    }
    
    public function addDataFromTmpTableByID($id) 
    {
        $sql = 'INSERT INTO `'.$this->table.'`
                SELECT * from `urls_providers_tmp` where urls_id = :urls_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':urls_id' => $id));
    }
    
}

?>

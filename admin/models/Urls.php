<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Urls
 *
 * @author dmitry
 */
class Urls extends BaseModel {
    
    public $id; 
    public $name;
    public $redirect_url;
    public $exception_url;
    public $ips;
    public $country;
    public $start;
    public $end;
    public $status;
    public $description;
    public $site_category;
    public $site_id;
    public $wp_post_id;
    public $desc_logo_url;
    public $desc_logo;
    public $param_url;
    public $published;
    
    protected $table = 'urls';
    
    public function getAdvWithEqualUrls() 
    {
        $uData = array();
        $sql = 'SELECT u.* FROM '.$this->table.' as u '.
        ' INNER JOIN site on u.site_id=site.id '.
        ' INNER JOIn roles as  r ON r.type = "advertiser" '.
        ' INNER JOIN advertiser_roles as a_r ON a_r.urls_id = u.id and a_r.role_id = r.id
            WHERE u.redirect_url = u.exception_url AND u.redirect_url != \'\' AND u.exception_url != \'\' AND site.less_strict=0';
        //redirect_url = exception_url AND exception_url != \'\' AND  redirect_url !=\'\' // old conditional
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute();
        return $smtm->fetchAll();        
    }
    
    public function getAdvWithMissingUrl()
    {
        $uData = array();
        $sql = 'SELECT u.* FROM '.$this->table.' as u '.
        ' INNER JOIN site on u.site_id=site.id '.
        ' INNER JOIn roles as  r ON r.type = "advertiser" '.
        ' INNER JOIN advertiser_roles as a_r ON a_r.urls_id = u.id and a_r.role_id = r.id
            WHERE site.less_strict=0 AND (u.redirect_url = \'\' OR u.exception_url = \'\') ';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute();
        return $smtm->fetchAll();
    }
    
    public function getUrlsDataByCustomClause($whereClause = '')
    {
        $uData = array();
        
        if(!empty($whereClause)) {
            
            $sql = 'SELECT * FROM '.$this->table.' WHERE '.$whereClause;
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute();
            $uData = $smtm->fetchAll();
        }
        
        return $uData;
    }


    /**
     * getTableData
     * @desc return array of advertisers for drow that data in the dataTable
     * @param int $site_id
     * @param tinyint $published
     * @param string $categoryData
     * @return array 
     */
    public function getTableData($site_id = null, $published = null, $categoryData = null, $columns = 'adver.*', $only_adver = false, $where = '')
    {
        $siteFilter = '';
        
        if(!empty($site_id))
            $siteFilter = ' WHERE site_id ='.$site_id;
        
        if($published === '0' ||
                $published == 1) {
            
            if(empty($siteFilter))
                $siteFilter = ' WHERE adver.published ='.$published;
            else $siteFilter .= ' AND adver.published ='.$published;
        }
        
        $category_filter = ''; 
        
        if(!empty($categoryData)) {
            
            $category_filter = ' INNER JOIN advertiser_category AS advcat ON advcat.urls_id = adver.id '; 
            $cData = explode('#', $categoryData);
            $category_id = $cData[0];
            $site_id = $cData[1];
            
            $condition = 'advcat.category_id ='.$category_id.' AND adver.site_id = '.$site_id; 
            if(empty($siteFilter))
                $siteFilter = ' WHERE '.$condition;
            else $siteFilter .= ' AND '.$condition;
        }

        if ($only_adver) {
            $roles = ' INNER JOIN roles AS r ON r.type = "advertiser" '.
            ' INNER JOIN advertiser_roles AS a_r ON a_r.urls_id = adver.id AND a_r.role_id = r.id';
        } else {
            $roles = '';
        }
        if (!empty($where)) {
            if(empty($siteFilter)) $siteFilter = ' WHERE '.$where;
            else $siteFilter .= ' AND '.$where;
        }
        $sql = 'SELECT '.$columns.', site.domain
                FROM '.$this->table.' as adver
                '.$category_filter.'
                '.$roles.'
                LEFT JOIN site ON site.id = adver.site_id
                '.$siteFilter.'
                GROUP BY adver.id ORDER By adver.name';
        
        return app::inst()->db->query($sql)->fetchAll();
    }
    
    /**
     * updateData
     * @param array $data should contained correct key, for example: array('name' => "Arv restourant", ... , 'id'=>14) where "id" is "urls_id";
     */
    public function updateData($data, $whereFields=array())
    {
        if(!empty($data)) {
            
            $set = ''; 
            $upData = array(); 
            $cnt = 0;
            foreach($data as $field => $value) {
                
                $placeholder = ':'.$field;
                $upData[$placeholder] = $value; 

                if($field!='id')
                    if($cnt!=0)
                        $set .= ',`'.$field.'` = '.$placeholder;
                    else $set .= '`'.$field.'` = '.$placeholder;
                
                $cnt++; 
            }
        }
        
        $sql = 'UPDATE `'.$this->table.'`
                SET '.$set.'
                WHERE  `id` = :id
            ';

        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute($upData);
    } 
    
    public function getUrlsData($id, $name = false)
    {
        $where = ''; 
        
        if($name) {
            
            $where = ' WHERE id=:id AND name=:name';
            $fData = array(':id' => $id, ':name'=>$name);
        } 
        else {
            
            $where = ' WHERE id=:id';
            $fData = array(':id' => $id);
        }
        
        $sql = 'SELECT * FROM '.$this->table.' '.$where;
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($fData);
        $uData = $smtm->fetch();
        
        return $uData;
    }
    
    public function deleteByID($id)
    {
        $sql = 'DELETE FROM '.$this->table.' where id= :id LIMIT 1';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':id' => $id));
    }
    
    public function deleteAllDataBySiteID($site_id)
    {
        $sql = 'DELETE FROM '.$this->table.' where site_id =:site_id';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':site_id' => $site_id));
    }
    
    public function revertBack($id)
    {
        $sql = 'DELETE FROM '.$this->table.' where id = :id and status = "added" LIMIT 1';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':id' => $id));
    }
    
    public function addEmptyField()
    {
        $sql = "INSERT INTO  ".$this->table." SET `status`='added'";
        app::inst()->db->query($sql);
        $id = app::inst()->db->lastInsertId();
        return $id;
    }
}

?>

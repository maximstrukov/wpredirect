<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Categories
 *
 * @author dmitry
 */
class Categories extends BaseModel 
{
    public $category_id;
    public $name;
    public $parent_id;
    public $site_id;
    
    protected $table = 'categories';
    
    public function addCategory($category_id, $name, $parent_id, $site_id)
    {
        $result = false; 
        $categoryIs = $this->getCategoryByID($category_id, $site_id);

        $aData = array(
            ':category_id' => $category_id,
            ':name' => $name,
            ':parent_id' => $parent_id,
            ':site_id' => $site_id
        );
        
        if($categoryIs) {
            
            $sql = "UPDATE ".$this->table." SET `name`= :name,
                                                `parent_id` = :parent_id,
                                                `site_id`= :site_id
                                            WHERE `category_id` = :category_id ";
        }
        else {
            
            $sql = "INSERT INTO ".$this->table." SET `category_id` = :category_id,
                                                     `name`= :name,
                                                     `parent_id` = :parent_id,
                                                     `site_id`= :site_id";                                    
        }

        $smtm = app::inst()->db->prepare($sql);
        $result = $smtm->execute($aData);
        return $result; 
    }

    public function renameCategory($old_name, $new_name, $site_id) 
    {
        $sql = "UPDATE ".$this->table." SET name=:new_name WHERE name = :old_name AND site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':new_name' => $new_name, ':old_name' => $old_name, ':site_id' => $site_id));
    }
    
    public function getCategoryByID($category_id, $site_id)
    {
        $result = array();
        $sql = "SELECT * FROM ".$this->table." WHERE category_id = :category_id AND site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':category_id' => $category_id, ':site_id' => $site_id));
        $result = $smtm->fetch();
        return $result;
    }
    
    public function getCategoryBySiteAndName($site_id, $category_name)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE name = :name and site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':name' => $category_name, ':site_id' => $site_id));
        $result = $smtm->fetch();
        return $result; 
    }
    
    public function getCategoriesBySiteID($site_id) 
    {
        $sql = "SELECT * FROM ".$this->table." WHERE site_id = :site_id ORDER By name";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':site_id' => $site_id));
        $result = $smtm->fetchAll();
        return $result;
    }

    public function getSubCategoriesByCategory($category, $site_id) 
    {
        $sql = "SELECT category_id FROM ".$this->table." WHERE name = :category AND site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':category' => $category, ':site_id' => $site_id));
        $result = $smtm->fetch();
        
        $category_id = $result["category_id"];
        
        $sql = "SELECT * FROM ".$this->table." WHERE parent_id = :category_id AND site_id = :site_id ORDER By name";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':category_id' => $category_id, ':site_id' => $site_id));
        $result = $smtm->fetchAll();
        return $result;
    }    
    
    public function deleteCategoryByID($category_id, $site_id) 
    {
        $sql = "DELETE FROM ".$this->table." WHERE category_id = :category_id AND site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':category_id' => $category_id, 
                                    ':site_id' => $site_id
                                        ));
    }
    
    public function deleteCategoriesBySiteID($site_id) 
    {
        $sql = "DELETE FROM ".$this->table." WHERE site_id = :site_id";
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':site_id' => $site_id));
    }
    
    public function getCategoriesWithSites($site_id = null) 
    {
        $where = '';
        $aData = array(); 
        
        if(!is_null($site_id)) {
            
            $where = ' WHERE  cat.site_id = :site_id '; 
            $aData = array(':site_id'=> $site_id);
        }
        
        $sql = "SELECT 
                    cat.category_id,
                    cat.name as category_name,
                    cat.site_id,
                    site.domain as site_name
                FROM ".$this->table." as cat 
                INNER JOIN site ON cat.site_id = site.id
        ".$where." ORDER BY site.domain"; 
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($aData);
        return $smtm->fetchAll();        
    }
}

?>

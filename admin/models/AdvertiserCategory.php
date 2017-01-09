<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdvertiserCategory
 *
 * @author dmitry
 */
class AdvertiserCategory extends BaseModel 
{
    public $urls_id;
    public $category_id;
    
    protected $table = 'advertiser_category';        
    
    public function assignAdvToCategory($category_id, $urls_id) 
    {
        $aData = array(
            ':urls_id' => $urls_id,
            ':category_id' => $category_id,
        ); 
        
        if(!$this->isAssignAdvToCategory($urls_id, $category_id)) {
            
            $sql = "INSERT INTO ".$this->table." SET `urls_id` = :urls_id,
                                                     `category_id`= :category_id
                                               ";            
            $smtm = app::inst()->db->prepare($sql);
            $result = $smtm->execute($aData);
        }
    }
    
    public function unassignAdvToCategories($urls_id)
    {
        $result = false; 
        $aData = array(
            ':urls_id' => $urls_id
        );        
        $sql = "DELETE FROM ".$this->table." WHERE `urls_id` = :urls_id";
        $smtm = app::inst()->db->prepare($sql);
        $result = $smtm->execute($aData);        
        return $result; 
    }
    
    public function assignAdvToCategories($urls_id, $categories_name_arr, $site_id)
    {

        $this->unassignAdvToCategories($urls_id);
        if(!empty($categories_name_arr)) {
           
            $categoriesModel = new Categories(); 
            foreach($categories_name_arr as $category_name) {
                $category_name = str_replace('&', '&amp;', $category_name);
                $category_name = str_replace('&amp;amp;','&amp;',$category_name);
                $categoryData = $categoriesModel->getCategoryBySiteAndName($site_id, $category_name);
                if(!empty($categoryData)) {
                    
                    $category_id = $categoryData['category_id'];
                    $this->assignAdvToCategory($category_id, $urls_id); 
                }
            }
        }
    } 
    
    public function isAssignAdvToCategory($urls_id, $category_id) 
    {
        $isAssign = false; 
        $sql = "SELECT * FROM ".$this->table." WHERE urls_id = :urls_id and category_id = :category_id";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':urls_id' => $urls_id, ':category_id' => $category_id));
        $result = $smtm->fetch();
        if(!empty($result)) $isAssign = true;
        
        return $isAssign;
    }
    
    /**
     * getCategoriesByAdvID
     * @param int $urls_id
     * @return string - return string with categories list
     */
    public function getCategoriesByAdvID($urls_id)
    {
        $categories = array();
        $sql = "SELECT * FROM ".$this->table." WHERE urls_id = :urls_id";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':urls_id' => $urls_id));
        $advCategoriesData = $smtm->fetchAll();
        $categoriesModel = new Categories();
        $urlsModel = new Urls(); 
        $uData = $urlsModel->getUrlsData($urls_id);
        $site_id = $uData['site_id']; 
        if(!empty($advCategoriesData) && !empty($site_id)) {
            foreach($advCategoriesData as $key => $acRow) {
                
                $category_id = $acRow['category_id'];
                $categoryData = $categoriesModel->getCategoryByID($category_id, $site_id);
                
                $categories[$key] = $categoryData;
            }
        }

        $mixedData = ''; 
        foreach($categories as $key => $categoryData) {
            $mixedData .= $this->getParentCat($categories, $categoryData['parent_id']);
            $mixedData .= $categoryData['name'].PHP_EOL.'<br />';
        }
        return $mixedData; 
    }
    
    private function getParentCat($categories, $parent_id) 
    {
        $parentCat = ''; 
        foreach($categories as $key => $categoryData) {
            
            if($parent_id == $categoryData['category_id']) {
                
                if($categoryData['parent_id'] != 0 && !empty($categoryData['parent_id']))
                    $parentCat .= $this->getParentCat($categories, $categoryData['parent_id']);
                
                $parentCat .= $categoryData['name']; 
            }
        }
        
        return !empty($parentCat)? $parentCat.'/' : $parentCat;
    }
    
    public function deleteByUrlsID($urls_id) 
    {
        $sql = "DELETE FROM ".$this->table." WHERE urls_id = :urls_id";
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':urls_id' => $urls_id));
    }
    
    /**
     * getCatByUrlAndSiteID
     * @param int $url_id
     * @param int $site_id
     * @return array  
     */
    public function getCatByUrlAndSiteID($url_id, $site_id) 
    {
        $result = array(); 
        if($url_id && $site_id) {
            
            $sql = "SELECT 
                        ".$this->table.".* , 
                        categories.*
                    FROM ".$this->table." 
                        INNER JOIN categories ON categories.site_id = :site_id AND categories.category_id = ".$this->table.".category_id
                    WHERE ".$this->table.".urls_id = :urls_id ";
            
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute(array(':urls_id' => $url_id, ':site_id' => $site_id));
            $result = $smtm->fetchAll();
        }
        return $result; 
    }
    
    /**
     * checkRoot
     * @desc check existing of parent category for current category
     * @param int $parent_id
     * @param array $advCategories
     * @return boolean 
     */
    public function checkRoot($parent_id, $advCategories)
    {
         foreach($advCategories as $catItem)  {
             if($catItem['category_id'] == $parent_id)
                 return true; 
         }
         return false; 
    }
    
    /**
     * checkParent
     * @desc check existing of subsidiary category for current category
     * @param int $root_id
     * @param array $advCategories
     * @return boolean 
     */
    public function checkParent($root_id, $advCategories)
    {
         foreach($advCategories as $catItem)  {
             if($catItem['parent_id'] == $root_id)
                 return true;
         }
         return false;         
    }
}

?>

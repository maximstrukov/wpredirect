<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Settings
 *
 * @author dmitry
 */

class Settings extends BaseModel {
    public $key;
    public $value;
    
    protected $table = 'settings';    
    
    public function getAllSettings()
    {
        $results = array();
        $allKeys = $this->getDataByFields(array(), true); 
        foreach ($allKeys as $row) {
            $results[$row['key']] = $row['value'];
        }
        return $results;     
    }
    
    /**
     * getSettingByKey
     * @desc return option value from setting by setting name (key)
     * @param string $key - name of option from settings
     * @return string; 
     */
    public function getSettingByKey( $key ) 
    {
        $results = false; 
        $results = $this->getDataByFields(array('key'=>$key));
        return $results['value'];
    }
    
}

?>

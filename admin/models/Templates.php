<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Templates
 *
 * @author dmitry
 */
class Templates extends BaseModel 
{
    public $id; 
    public $name;
    
    protected $table = 'templates';
    
    public function getTemplateByID($template_id)
    {
        $result = array();
        $sql = "SELECT
                     temp.id as tempalte_id,
                     temp.name as name, 
                     idata.id as isp_data_id,
                     idata.country, 
                     idata.isp_data
                FROM ".$this->table." as temp
                    INNER JOIN template_isp as tisp ON temp.id = tisp.template_id
                    LEFT JOIN isp_data as idata ON idata.id = tisp.isp_data_id
                WHERE temp.id = :template_id ";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':template_id' => $template_id));
        $result = $smtm->fetchAll();
        return $result;
    }
}

?>

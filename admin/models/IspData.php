<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IspData
 *
 * @author dmitry
 */
class IspData extends BaseModel 
{
    public $id;
    public $country;
    public $isp_data;
    
    protected $table = 'isp_data';
}

?>

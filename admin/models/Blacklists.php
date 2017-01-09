<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Blacklists
 *
 * @author dmitry
 */
class Blacklists extends BaseModel 
{
    public $id; 
    public $name;
    public $ips_data;
    public $type;
    
    protected $table = 'blacklists';
    
}

?>

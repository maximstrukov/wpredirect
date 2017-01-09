<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SiteBlacklist
 *
 * @author dmitry
 */
class SiteBlacklist extends BaseModel 
{
    public $site_id; 
    public $blacklist_id;
    
    protected $table = 'site_blacklist';
    
}

?>

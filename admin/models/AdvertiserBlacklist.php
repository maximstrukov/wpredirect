<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdvertiserBlacklist
 *
 * @author dmitry
 */
class AdvertiserBlacklist extends BaseModel 
{
    public $urls_id; 
    public $blacklist_id;
    
    protected $table = 'advertiser_blacklist';
    
}

?>

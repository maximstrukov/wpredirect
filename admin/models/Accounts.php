<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Accounts
 *
 * @author dmitry
 */
class Accounts extends BaseModel 
{
    public $id; 
    public $name;
    public $rule;    
    
    protected $table = 'accounts';
}

?>

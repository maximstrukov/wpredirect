<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Auth
 *
 * @author dmitry
 */

class Auth {

    public static function registration($email, $pass) 
    {   
        $password = md5($pass);
        $sql = 'INSERT INTO `users` SET email = :email, pass = :pass';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':email' => $email, ':pass' => $password));
        
        return;
    }
    
    public static function hasIdentity() 
    {    
        $hasIdentity = false; 
        if(isset($_SESSION['user_id']) &&
                !empty($_SESSION['user_id']))
            $hasIdentity = true; 
        
        return $hasIdentity;
    }

    public static function authenticate($email, $pass) 
    {
        $isReg = false; 
        $password = md5($pass);
        $sql = "SELECT `id` FROM `users`
                WHERE `email` = :email AND `pass`= :pass LIMIT 1";        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':email' => $email, ':pass' => $password));
        $identRes = $smtm->fetch();
        
        if(!empty($identRes)) {
            
            $_SESSION['user_id'] = $identRes['id'];
            $isReg = true; 
        }
            
        return $isReg;
    }

    public static function getIdentity() 
    {    
        $getIdentity = false; 
        if(isset($_SESSION['user_id']) &&
                !empty($_SESSION['user_id']))     
            $getIdentity = $_SESSION['user_id']; 
        
        return $getIdentity;
    }

    public static function clearIdentity() 
    {
        unset($_SESSION['user_id']);
    }    
}

?>

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LoginController
 *
 * @author dmitry
 */
class LoginController extends AController {
    
    public function indexAction() {
        
        if(Auth::hasIdentity())
            header('Location: /admin/index.php?cont=campaign&act=index');
        
        $result = array('error'=>false);
        
        if (isset($_POST['email']) && isset($_POST['pass'])) {
            
            $email = $_POST['email'];
            $pass = $_POST['pass'];
            
            if(Auth::authenticate($email, $pass)) 
                header('Location: /admin/index.php?cont=campaign&act=index');
            else $result = array('error'=>true);
            
        }
        
        $this->render('index', $result, true);
    }    
    
    public function logoutAction() {
        
        Auth::clearIdentity();
        header('Location: /index.php?cont=login');
    }
}

?>

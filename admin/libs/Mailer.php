<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Mailer
 *
 * @author dmitry
 */

require_once 'mailer/swift_required.php';

class Mailer {
    
    public static function sendHtmlMail($from, $to, $subject, $body = null, $attachments = array())
    {
        $message = Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom($from)
        ->setTo($to);
        
        // example set image in letters 
        // $body .= '<img src="'.$message->embed(Swift_Image::fromPath('images/vote_now.jpg')).'" />';
        
        if(!is_null($message))
            $message->setBody($body);
        
        // set smtp server
        $transport = Swift_SmtpTransport::newInstance('localhost', 25);
        // ->setUsername('username')
        // ->setPassword('password');        
        
        $mailer = Swift_Mailer::newInstance($transport);
        return $mailer->send($message); 
    }
}

?>

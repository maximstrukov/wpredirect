<?php

/**
 * Description of Site
 *
 * @author ivan
 */
class Site extends BaseModel {
    public $id;
    public $domain;
    public $wp_login;
    public $wp_pass;
    public $logo_data;
    public $email;
    public $use_bl;
    public $ftp_data;  
    public $status; 
    public $curl_status; 
    public $less_strict;
    public $project;
    public $show_iframe;
    public $check_advs;
    
    protected $table = 'site';

    public function rules() {
        return array(
            array('domain, wp_login, wp_pass, country, logo_data', 'required'),
            array('domain', 'unique'),
            array('domain', 'checkWP'),
            array('email', 'checkEmail')
        );
    }
    
    public function checkEmail()
    {
        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            
            $this->addError('email', 'Please enter a valid email address');
        }
    }
    
    public function checkWP($field)
    {
        # check WP RPC
        if(!empty($this->domain) &&
                !empty($this->wp_login) &&
                        !empty($this->wp_pass)) {
            
            $domain = WpAdmin::fetchFormattedDomain($this->domain);
            $this->domain = $domain;
            $rpcUrl = 'http://'.$this->domain."/xmlrpc.php";
            $objXMLRP = new XMLRPClientWordPress( $rpcUrl, $this->wp_login , $this->wp_pass);
            $response = $objXMLRP->getUserInfo(); 
            $rObj = simplexml_load_string($response);
            $name = isset($rObj->params->param->value->struct->member[0]->name) ? $rObj->params->param->value->struct->member[0]->name : null;
            if($name!='nickname') {
                $this->addError('domain', 'Please enable XML-RPC at wordpress site or check WP login and pass.');
            }
        }
    }
    
    public function addSite() 
    {    
        $sql = "INSERT INTO ".$this->table." SET `domain`= :domain,
                                    `country`=:country,
                                    `wp_login`= :wp_login,
                                    `wp_pass`= :wp_pass,
                                    `logo_data`= :logo_data, 
                                    `ftp_data`= :ftp_data, 
                                    email = :email,
                                    use_bl = :use_bl,
                                    less_strict = :less_strict,
                                    project = :project,
                                    show_iframe = :show_iframe,
                                    check_advs = :check_advs
                    ";            
        
        $email = $this->email ? $this->email : '';
        $use_bl = ($this->use_bl == 1) ? 1 : 0;
        $less_strict = ($this->less_strict == 1) ? 1 : 0;
        
        $insData = array(
            ':domain' => $this->domain,
            ':country' => strtoupper($this->country),
            ':wp_login' => $this->wp_login,
            ':wp_pass' => $this->wp_pass,
            ':logo_data' => $this->logo_data,
            ':ftp_data' => $this->ftp_data,
            ':email' => $email, 
            ':use_bl' => $use_bl,
            ':less_strict' => $less_strict,
            ':project' => $this->project,
            ':show_iframe' => $this->show_iframe,
            ':check_advs' => 0
        );         
        
        $smtm = app::inst()->db->prepare($sql);
        $result = $smtm->execute($insData);
        
        return $result;
    }
    
    public function updateSite($id) 
    {
        $sql = "UPDATE ".$this->table." SET `domain` = :domain ,
                                `country` =:country,
                                `wp_login`= :wp_login,
                                `wp_pass` = :wp_pass,
                                `logo_data`= :logo_data,
                                `ftp_data`= :ftp_data,
                                `email`= :email,
                                use_bl = :use_bl,
                                less_strict = :less_strict,
                                project = :project,
                                show_iframe = :show_iframe
                            WHERE `id` = :id";
        
        $email = $this->email ? $this->email : '';
        $use_bl = ($this->use_bl == 1) ? 1 : 0;
        $less_strict = ($this->less_strict == 1) ? 1 : 0;
        
        $upData = array(
            ':domain' => $this->domain,
            ':country' => strtoupper($this->country),
            ':wp_login' => $this->wp_login,
            ':wp_pass' => $this->wp_pass,
            ':logo_data' => $this->logo_data,
            ':ftp_data' => $this->ftp_data,
            ':id' => $id,
            ':email' => $email,
            ':use_bl' => $use_bl,
            ':less_strict' => $less_strict,
            ':project' => $this->project,
            ':show_iframe' => $this->show_iframe
        ); 
        
        $smtm = app::inst()->db->prepare($sql);
        $result = $smtm->execute($upData);
        
        return $result;
    }
    
    public function getSites($site_id = null, $cols = "*", $where = "")
    {
        
        if(!is_null($site_id)) {
            if (!empty($where)) $where = " AND ".$where;
            $sql = "SELECT ".$cols." FROM ".$this->table." WHERE id = :id".$where." ORDER BY domain";
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute(array(':id' => $site_id));
            $result = $smtm->fetch();
        }
        else {
            if (!empty($where)) $where = " WHERE ".$where;
            $sql = "SELECT ".$cols." FROM ".$this->table.$where." ORDER BY domain";
            $result = app::inst()->db->query($sql)->fetchAll();
        }
        
        return $result;
    }
    
    public function getSitebyDomain($domain) {
        $sql = "SELECT * FROM ".$this->table." WHERE domain = :domain";
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':domain' => $domain));
        $result = $smtm->fetch();
        return $result;
    }
    
    public function deleteByID($id)
    {
        $sql = 'DELETE FROM '.$this->table.' where id= :id LIMIT 1';
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute(array(':id' => $id));
    }    
    
    public function getLessStrictVal($site_id)
    {
        $result = false; 
        if($site_id) {
            $sData = $this->getDataByFields(array('id'=>(int)$site_id));        
            $result = ($sData['less_strict']) ? true : false;
        }
        
        return $result; 
    }
}
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HidingController
 *
 * @author dmitry
 */
class HidingController extends BaseController {
    
    public function __construct() 
    {   
        parent::__construct();
    
        $this->registerScriptFile('js/app.hiding.js');
    }
    
    public function indexAction() 
    {
        $siteModel = new Site(); 
        $sData = $siteModel->getDataByFields(array(), true);

        $this->render('index', array(
            'sites'=>$sData,
        ));        
    }    
    
    public function getaccountAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $accountsModel = new Accounts(); 
        $aData = $accountsModel->getDataByFields(array(), true);
        
        $resArr = array();
        foreach($aData as $row) {
            
            $rRow = array();
            $rRow[] = $row['name'];
            $rRow[] = $row['rule'];
            $rRow[] = '<a href="#" account_id="'.$row['id'].'" class="edit" onclick="account_edit(this); return false;" name="'.$row['name'].'" >edit</a> <a href="#" account_id="'.$row['id'].'" name="' . $row['name'] . '" class="delete" onclick="account_delete(this); return false;">delete</a>';
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }
    
    public function editaccountAction()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');        
        
        $accountsModel = new Accounts(); 
        
        $account_id = (isset($_POST['account_id']) && !empty($_POST['account_id'])) ? $_POST['account_id'] : false;
        $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $_POST['name'] : false;
        $rule = (isset($_POST['rule']) && !empty($_POST['rule'])) ? $_POST['rule'] : false;
        
        $data = array(
                        'name'=>$name,
                        'rule'=>$rule
                            ); 
        
        if($account_id) {
            $accountsModel->updateData($data, array('id'=>$account_id));
        }
        else if($name && $rule) {
            
            $accountsModel->insertData($data); 
        }
        
        echo json_encode(array('done'));
        exit();        
    }
    
    public function deleteaccountAction()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $accountsModel = new Accounts(); 
        
        $account_id = (isset($_POST['id']) && !empty($_POST['id'])) ? $_POST['id'] : false;
        
        if($account_id) {
            
            $accountsModel->deleteData(array('id'=>$account_id)); 
        }
        
        echo json_encode('ok');
        exit();        
    }
    
    public function getaccountinfoAction()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');        
        
        $aData = array(); 
        
        $account_id = (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) ? $_REQUEST['account_id'] : false;
        
        $accountsModel = new Accounts();
        
        $aData = ($account_id) ? $accountsModel->getDataByFields(array('id'=>$account_id)) : 
                    $accountsModel->getDataByFields(array(), true);
        
        echo json_encode($aData);
        exit();                
    }
    
    public function runAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $site_id = (isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id'])) ? $_REQUEST['site_id'] : false;
        $account_id = (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) ? $_REQUEST['account_id'] : false;
        $type = (isset($_REQUEST['type']) && !empty($_REQUEST['type'])) ? $_REQUEST['type'] : false;
        
        
        $urlsModel = new Urls(); 
        $siteModel = new Site(); 
        
        $accountModel = new Accounts();
        $aData = $accountModel->getDataByFields(array('id'=>$account_id));

        $account_name = $aData['name']; 
        $rule = isset($aData['rule']) ? $aData['rule'] : ''; 
        $eRules = explode(PHP_EOL, $rule);

        $wClause = ''; 
        $cnt =0; 
        
        $bracket = false; 
        
        foreach ($eRules as $rItem) {

            $wRule = preg_replace('/\h+|\r+|\n+/', '', $rItem);
            
            if(count($eRules)>1) {
               
                $bracket = true;
                
                if($cnt == 0) 
                    $wClause .= '( exception_url LIKE \'%'.$wRule.'%\' '; 
                else $wClause .= ' OR exception_url LIKE \'%'.$wRule.'%\' '; 
            }
            else {
                
                $wClause .= ' exception_url LIKE \'%'.$wRule.'%\' ';
            }
            
            $cnt++;
        }
        
        $wClause .= ($bracket) ? ')':''; 
        
        $uData = array(); 

        if(!empty($wClause)) {

            $wClause .= ' AND site_id = '.$site_id.' '; 

            $uData = $urlsModel->getCustomData($wClause, array('wp_post_id','name','id'));
        }        
        
        switch ($type) {
            
            case 'dry-mode':
                
                echo json_encode(array('type'=>$type, 'data'=>$uData));
                exit();    
               
                break;
                
            case 'hide':
                
                $state = false;
                $result = WpAdmin::setPublished($state, $uData, $site_id); 
                break;
            case 'show':
                
                $state = true;
                $result = WpAdmin::setPublished($state, $uData, $site_id); 
                break;
        }        
        
        echo json_encode('set'); 
        exit();     
    }
}

?>

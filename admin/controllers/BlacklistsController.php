<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BlacklistsController
 *
 * @author dmitry
 */
class BlacklistsController extends BaseController 
{
    public function indexAction()
    {
        $this->registerScriptFile('js/app.blacklists.js');
        
        $data = array();
        
        $this->render('index', array(
            'data'=>$data,
        ));
    }
    
    public function listAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $blacklistsModel = new Blacklists();
        $result = $blacklistsModel->getDataByFields(array(), true);
        
        $resArr = array();
        foreach($result as $row){
            
            $rRow = array();
            $rRow[] = $row['name'];
            $rRow[] = $row['type'];
            $rRow[] = '<a href="#" blacklist_id="' . $row['id'] . '" class="edit" onclick="open_edit(this); return false;">edit</a> <a href="#" blacklist_id="' . $row['id'] . '" name="' . $row['name'] . '" class="delete" onclick="open_delete(this); return false;">delete</a>';
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }
    
    function getinfoAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $response = array();
        if (isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
            
            $blacklistsModel = new Blacklists();
            $response = $blacklistsModel->getDataByFields(array('id'=>$_REQUEST['id']));
        } 
        
        echo json_encode($response);
        exit();
    }
    
    public function saveAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $blacklistsModel = new Blacklists();
        
        $blacklist_id = (isset($_REQUEST['id'])&&!empty($_REQUEST['id'])) ? $_REQUEST['id'] : false;
        
        $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $_POST['name'] : '';
        $ips_data = (isset($_POST['ips_data']) && !empty($_POST['ips_data'])) ? $_POST['ips_data'] : '';
        $type = (isset($_POST['type']) && !empty($_POST['type'])) ? $_POST['type'] : '';
        $tu_data = (isset($_POST['tu_data']) && !empty($_POST['tu_data'])) ? $_POST['tu_data'] : '';
        
        if ($_POST) {
            
            $data = array(
                    'name' => $name,
                    'ips_data' => $ips_data,
                    'type' => $type,
                    'tu_data' => $tu_data
                            );
            
            if($blacklist_id) {
                
                $blacklistsModel->updateData($data, array('id'=>$blacklist_id));
                
                if($type == 'public'
                        || $type == 'private_tu') {
                    
                    //public and private_tu blacklists can't be assign with site and advertisers
                    $abModel = new AdvertiserBlacklist();
                    $abModel->deleteData(array('blacklist_id'=>$blacklist_id));
                    
                    $sbModel = new SiteBlacklist();
                    $sbModel->deleteData(array('blacklist_id'=>$blacklist_id));
                }
            } 
            else $blacklistsModel->insertData($data);
        }

        echo json_encode("saved");
        exit();
    }
    
    function deleteAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {
            
            $blacklistsModel = new Blacklists();
            $blacklistsModel->deleteData(array('id'=>$_REQUEST['id']));
            
            $abModel = new AdvertiserBlacklist(); 
            $abModel->deleteData(array('blacklist_id'=>$_REQUEST['id']));
            
            $sbModel = new SiteBlacklist(); 
            $sbModel->deleteData(array('blacklist_id'=>$_REQUEST['id']));
        }
        
        echo json_encode('ok');
        exit();
    }    
}

?>

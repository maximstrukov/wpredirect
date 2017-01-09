<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TemplatesController
 *
 * @author dmitry
 */
class TemplatesController extends BaseController 
{
    public function indexAction()
    {
        $this->registerScriptFile('js/app.templates.js');
        
        $data = array();
        
        $countryCodes = $this->getCountryCodes();
        
        $this->render('index', array(
            'data'=>$data,
            'countryCodes'=>$countryCodes,
        ));
    }
    
    public function importtemplateAction()
    {
        $error = false ;
        $result = false ;
        
        if(isset($_FILES) && 
                !empty($_FILES)) {
            
            if ($_FILES["uploadedfile"]["error"] > 0) {
                
                $error =  "Return Code: " . $_FILES["uploadedfile"]["error"];
            }    
            else {

                $file_name = $_FILES['uploadedfile']['name'];
                $type = $_FILES['uploadedfile']['type'];

                if(strpos($file_name, 'csv') &&
                        strpos($type, 'csv')) {

                    // csv: WP_SITE_NAME; ADV_NAME; TEMPLATE_NAME
                    $file = $_FILES['uploadedfile']['tmp_name']; 
                    
                    $checkHandle = fopen($file, "r");
                    $handle = fopen($file, "r");

                    // validation of csv-file content 
                    $nonSite = array(); // non-existing site
                    $nonTemp = array(); // non-existing template
                    $nonAdv = array(); // non-existing advertiser
                    
                    $siteModel = new Site();                    
                    $tempModel = new Templates();
                    $urlsModel = new Urls(); 
                    
                    while (($data = fgetcsv($checkHandle, 100, ",")) !== false) {
                        
                        $tData = array(); 
                        $sData = array(); 
                        $uData = array();                        
                        
                        $hostName = $data[0];
                        $adverName = trim($data[1]);
                        $tempName = trim($data[2]);
                        
                        // check exist of site 
                        $domain = WpAdmin::fetchFormattedDomain($hostName); 
                        $sData = $siteModel->getDataByFields(array('domain' => $domain ));
                        if(empty($sData)) $nonSite[] = $hostName;
                        
                        // check exist of advertiser 
                        if(!empty($sData)) {
                            
                            $site_id = $sData['id']; 
                            $uData = $urlsModel->getDataByFields(array('site_id'=>$site_id, 'name'=>$adverName));
                            if(empty($uData)) $nonAdv[] = 'where site = "'.$hostName.'" and name = "'.$adverName.'" ';
                        }
                        
                        // check exist of template
                        $tData = $tempModel->getDataByFields(array('name'=>$tempName));
                        if(empty($tData)) $nonTemp[] = $tempName;
                    }
                    
                    if(empty($nonSite) &&
                        empty($nonAdv) &&
                            empty($nonTemp)) {
                        
                        while (($data = fgetcsv($handle, 100, ",")) !== false) {

                            $tData = array(); 
                            $sData = array(); 
                            $uData = array(); 

                            $template_id = false; 
                            $urls_id = false; 

                            $hostName = $data[0];
                            $adverName = trim($data[1]);
                            $tempName = trim($data[2]);

                            // get site_id 
                            $domain = WpAdmin::fetchFormattedDomain($hostName);

                            $sData = $siteModel->getDataByFields(array('domain' => $domain ));
                            $site_id = $sData['id'];

                            // get tepmplate_id 
                            $tData = $tempModel->getDataByFields(array('name'=>$tempName));
                            $template_id = $tData['id'];

                            $advTempModel = new AdvertiserTemplate(); 

                            if(!strpos($adverName,'*')) {

                                // get urls_id    
                                $uData = $urlsModel->getDataByFields(array('site_id'=>$site_id, 'name'=>$adverName)); 
                                $urls_id = $uData['id'];

                                if($template_id && 
                                    $urls_id) {

                                    $atData = array();         
                                    $atData = $advTempModel->getDataByFields(array('urls_id'=>$urls_id));

                                    if(!empty($atData))
                                        $advTempModel->updateData(array('template_id' => $template_id), array('urls_id'=>$urls_id));
                                    else $advTempModel->insertData(array('template_id' => $template_id,'urls_id'=>$urls_id));
                                }
                            }
                            else {

                                // get urls_ids 
                                $uData = $urlsModel->getDataByFields(array('site_id'=>$site_id), true);

                                if(!empty($uData)) {

                                    foreach($uData as $uItem) {

                                        $urls_id = $uItem['id'];

                                        $atData = array();         
                                        $atData = $advTempModel->getDataByFields(array('urls_id'=>$urls_id));

                                        if(!empty($atData))
                                            $advTempModel->updateData(array('template_id' => $template_id), array('urls_id'=>$urls_id));
                                        else $advTempModel->insertData(array('template_id' => $template_id,'urls_id'=>$urls_id));                                    
                                    }
                                }
                            }
                        }
                        
                        $result = 'All data was successfully imported';
                    }
                    else {
                        
                        if(!empty($nonSite)) {
                            
                            $error = 'Non-existing Site(s) in system : <br />'; 
                            
                            foreach ($nonSite as $nonExistSite) {
                                
                                $error .= $nonExistSite.'<br />';
                            }
                        }
                        
                        if(!empty($nonTemp)) {
                            
                            $error .= 'Non-existing Template(s) in system : <br />'; 
                            
                            foreach ($nonTemp as $nonExistTemplate) {
                                
                                $error .= $nonExistTemplate.'<br />';
                            }
                        }
                        
                        if(!empty($nonAdv)) {
                            
                            $error .= 'Non-existing Advertiser(s) in system : <br />'; 
                            
                            foreach ($nonAdv as $nonExistAdvData) {
                                
                                $error .= $nonExistAdvData.'<br />';
                            }
                        }
                    }
                }
                else $error = 'File is not correct. Invalid file extension.';
            }
        }
        
        $this->render('importtemplate', array(    
            'error'=>$error,
            'result'=>$result
        ));
    }
    
    public function listAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $templatesModel = new Templates();
        $result = $templatesModel->getDataByFields(array(), true);
        
        $resArr = array();
        foreach($result as $row) {
            
            $rRow = array();
            $rRow[] = $row['name'];
            $rRow[] = '<a href="#" template_id="' . $row['id'] . '" class="edit" onclick="open_edit(this); return false;">edit</a> <a href="#" template_id="' . $row['id'] . '" name="' . $row['name'] . '" class="delete" onclick="open_delete(this); return false;">delete</a>';
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
        
        // clear isp tmp data
        unset($_SESSION['tmp_isp_data']);    
        
        // set current isp_data from db to session
        $template_id = (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) ? $_REQUEST['id'] : false;
        $this->setSession($template_id); 
            
        $response = array();
        if (isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
            
            $templatesModel = new Templates();
            $tempData = $templatesModel->getTemplateByID($_REQUEST['id']);
            
            foreach ($tempData as $tempItem) {
                
                $response[] = array(
                    'tempalte_id' => $tempItem['tempalte_id'],
                    'name' => $tempItem['name'],
                    'isp_data_id' => $tempItem['isp_data_id'],
                    'country' => $tempItem['country'],
                    'isp_data' => @unserialize($tempItem['isp_data'])
                );
            }
        }
        
        echo json_encode($response);
        exit();
    }
    
    private function setSession($template_id)
    {
        if($template_id) {
            
            $tempIspModel = new TemplateIsp();
            $ispDataModel = new IspData();                

            // get data and pop to session if country not changed
            $tiData = $tempIspModel->getDataByFields(array('template_id'=>$template_id));
            $isp_data_id = $tiData['isp_data_id'];



            $iData = $ispDataModel->getDataByFields(array('id'=>$isp_data_id));
            $iCountry = $iData['country'];

            // pop isp_data to session
            $ispData = unserialize($iData['isp_data']);
            foreach ($ispData as $isp_name)
                $_SESSION['tmp_isp_data'][$iCountry][] = $isp_name;
        }
        
        return true; 
    }
    
    public function saveAction()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');        
        
        $template_id = (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) ? $_REQUEST['id'] : false;
        $name = (isset($_REQUEST['name']) && !empty($_REQUEST['name'])) ? $_REQUEST['name'] : false;
        $country = (isset($_REQUEST['exception_country']) && !empty($_REQUEST['exception_country'])) ? $_REQUEST['exception_country'] : false;
        
        $templatesModel = new Templates();
        $tempIspModel = new TemplateIsp();
        $ispDataModel = new IspData();
        
        if (!$template_id) {
            
            // create new template  
            $template_id = $templatesModel->insertData(array('name'=>$name));
            
            // create isp data 
            if(isset($_SESSION['tmp_isp_data']) &&
                    !empty($_SESSION['tmp_isp_data'])) {
                
                $isp_data_ids = array(); 
                foreach($_SESSION['tmp_isp_data'] as $country => $ispArr) {
                    
                    $ispData = @serialize($ispArr);
                    $isp_data_ids[] = $ispDataModel->insertData(array(
                        'country' => $country,
                        'isp_data' => $ispData
                    ));
                } 
            }
            
            // assign template to isp_data 
            foreach($isp_data_ids as $isp_data_id) {
                
                $tempIspModel->insertData(array(
                    'template_id'=>$template_id,
                    'isp_data_id'=>$isp_data_id
                ));
            }
        } 
        else if(intval($template_id)) {
           
            //update name of template 
            $templatesModel->updateData(array('name'=>$name), array('id' => $template_id));
            
            // get isp_data_id by template_id 
            $tiData = $tempIspModel->getDataByFields(array('template_id'=>$template_id));
            $isp_data_id = $tiData['isp_data_id'];
            
            if(intval($isp_data_id)) {
                
                // update isp_data in the isp_data db table
                if(isset($_SESSION['tmp_isp_data'])) {
                    
                    if(empty($_SESSION['tmp_isp_data'])) {
                        
                        $ispData = @serialize(array());
                        $ispDataModel->updateData(array('isp_data'=>$ispData, 'country'=>$country), array('id'=>$isp_data_id));
                    } 
                    else foreach ($_SESSION['tmp_isp_data'] as $country => $ispArr) {
                        
                            $ispData = @serialize($ispArr);
                            $ispDataModel->updateData(array('isp_data'=>$ispData, 'country'=>$country), array('id'=>$isp_data_id)); 
                        }
                }
            }
        }
        
        // clear the stored information
        unset($_SESSION['tmp_isp_data']);        
        
        echo json_encode('Saved');
        exit();
    }
    
    public function addispAction() 
    {    
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        if (isset($_REQUEST['id'])) {
            
            if(!empty($_REQUEST['country_code']) &&
                    !empty($_REQUEST['isp_name'])) {
                
                if($_REQUEST['status']=='added') {
                    
                    if(!in_array($_REQUEST['isp_name'], $_SESSION['tmp_isp_data'][$_REQUEST['country_code']]))
                        $_SESSION['tmp_isp_data'][$_REQUEST['country_code']][] = $_REQUEST['isp_name'];
                }
                else if($_REQUEST['status']=='deleted') {
                    
                    foreach($_SESSION['tmp_isp_data'][$_REQUEST['country_code']] as $key => $ispName) {
                        
                        if($ispName == $_REQUEST['isp_name'])
                            unset($_SESSION['tmp_isp_data'][$_REQUEST['country_code']][$key]);
                    }
                }
            }
        }
        
        $response = $_REQUEST['status'];
        echo json_encode($response);
        exit();
    }    
    
    public function cancelAction()
    {
        // clear isp tmp data
        unset($_SESSION['tmp_isp_data']);
        
        echo json_encode('Done');
        exit();
    }
    
    public function setispbycountAction()
    {
        $country = isset($_REQUEST['country_code'])&&!empty($_REQUEST['country_code']) ? trim($_REQUEST['country_code']) : false; 
        $count = isset($_REQUEST['count'])&&!empty($_REQUEST['count']) ? (int)$_REQUEST['count'] : false; 
        $clear = (isset($_REQUEST['clear']) && $_REQUEST['clear'] == 'true' ) ? true : false;
        
        if($clear) {
            
            unset($_SESSION['tmp_isp_data'][$country]); 
            echo json_encode(array());
            exit();
        }
        
        if($country) {
            
            //clear session 
            unset($_SESSION['tmp_isp_data'][$country]); 
            
            $advIspModel = new UrlsIsps(); 
            $aiData = $advIspModel->getDataByFields(array('country_code'=>$country), true, array('IPnumbers DESC'), array('*'), array(), $count);
            
            foreach($aiData as $ispItem) {
                
                $isp_name = $ispItem['isp_name']; 
                $_SESSION['tmp_isp_data'][$country][] = $isp_name; 
            }
        }
        
        echo json_encode($_SESSION['tmp_isp_data'][$country]);
        exit();
    }
    
    public function selsearchedispAction()
    {
        $country = isset($_REQUEST['country_code'])&&!empty($_REQUEST['country_code']) ? trim($_REQUEST['country_code']) : false; 
        $part_isp_name = isset($_REQUEST['isp_name'])&&!empty($_REQUEST['isp_name']) ? trim($_REQUEST['isp_name']) : false; 
        $select = (isset($_REQUEST['select']) && $_REQUEST['select'] == 'true' ) ? true : false;

        $advIspModel = new UrlsIsps(); 
        $aiData = $advIspModel->searchIspDataByName($part_isp_name, $country);
        
        if($select) {
            //clear session 
            unset($_SESSION['tmp_isp_data'][$country]);                     
            
            foreach($aiData as $ispItem) {
                
                $isp_name = $ispItem['isp_name'];
                $_SESSION['tmp_isp_data'][$country][] = $isp_name;
            }
        }
        else {
            
            $isp_names = array();
            
            foreach($aiData as $ispItem)     
                $isp_names[] = $ispItem['isp_name'];
            
            $_SESSION['tmp_isp_data'][$country] = array_diff($_SESSION['tmp_isp_data'][$country], $isp_names);
        }
        
        $result = isset($_SESSION['tmp_isp_data'][$country]) ? $_SESSION['tmp_isp_data'][$country] : array(); 
        echo json_encode($result);
        exit();        
    }
    
    public function deleteAction()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {
            
            $tempModel = new Templates(); 
            $tempModel->deleteData(array('id'=>$_REQUEST['id']));
            
            $tempIspModel = new TemplateIsp(); 
            $tiData = $tempIspModel->getDataByFields(array('template_id'=>$_REQUEST['id']), true);
            
            $ispDataModel = new IspData();
            foreach($tiData as $tiItem) {
                
                $isp_data_id = $tiItem['isp_data_id'];
                $ispDataModel->deleteData(array('id'=>$isp_data_id));
            }
            
            $tempIspModel->deleteData(array('template_id'=>$_REQUEST['id']));
        }
        
        echo json_encode('ok');
        exit();
    }
}
?>

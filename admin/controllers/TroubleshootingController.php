<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TroubleshootingController
 *
 * @author dmitry
 */

class TroubleshootingController extends BaseController {
    
    public function __construct() 
    {   
        parent::__construct();
    
        $this->registerScriptFile('js/app.troubleshooting.js');
    }
    
    public function indexAction()
    {
        $data = array();
        
        $this->render('index', array(
            'data'=>$data,
        ));
    }
    
    public function onemainorsubcatAction() 
    {
        $site_id = (!empty($_REQUEST['site_id']) && intval($_REQUEST['site_id']) > 0) ? intval($_REQUEST['site_id']) : false;
        
        // get site list
        $siteModel = new Site(); 
        $allSites = $siteModel->getSites();
        
        if ($site_id) $sites = array($site_id);
        elseif (isset($_REQUEST['site_id']) && $_REQUEST['site_id']=="all") {
            $sites = $siteModel->getSites(null,"id");
            $site_id = "all";
        }
        else {
            $sites = array();
            $site_id = "";
        }
        $data = array(); 
        
        foreach ($sites as $site) {
            
            if (is_array($site)) $id = $site[0];
            else $id = $site;
            $catModel = new Categories(); 
            $allcats = $catModel->getCategoriesBySiteID($id);             
            
            $urlsModel = new Urls();
            $aData = $urlsModel->getTableData($id, 1, null, 'adver.*', true);

            foreach ($aData as $advItem) {
                
                $url_id = $advItem['id']; 
                
                $advcatModel = new AdvertiserCategory();
                
                $advCategories = $advcatModel->getCatByUrlAndSiteID($url_id, $id); 
                
//                echo '<pre>'; 
//                echo 'advertisers categories <br />'; 
//                print_r($advCategories);
//                echo 'all categories <br />'; 
//                print_r($allcats);
//                die();                    
                
                //is_root or is_parent
                foreach($advCategories as $catItem) {
                    
                    if($advcatModel->checkParent($catItem['category_id'], $allcats)) {
                        if(!$advcatModel->checkParent($catItem['category_id'], $advCategories)) {
                            $data[] = $advItem; 
                            break;
                        }
                    }
                    
                    if(($catItem['parent_id'] != 0) &&
                        !$advcatModel->checkRoot($catItem['parent_id'], $advCategories)) {
                            $data[] = $advItem; 
                            break;
                    }
                }
            }
        }
        
        $this->render('onemainorsubcat', array(
            'data'=>$data,
            'all_sites'=>$allSites,
            'site_id'=>$site_id,
        ));        
    }
    
    public function equalurlsAction()
    { 
        $data = array();
        
        $this->render('equalurls', array(
            'data'=>$data,
        ));        
    }
    
    public function equallistAction()
    {        
        $urlsModel = new Urls();
        //$result = $urlsModel->getUrlsDataByCustomClause('redirect_url = exception_url AND exception_url != \'\' AND  redirect_url !=\'\'');
        $result = $urlsModel->getAdvWithEqualUrls();
        
        $resArr = array();
        foreach($result as $row) {
            
            $rRow = array();
            $rRow[] = '<input type="checkbox" name="check_adv[]" _id="'.$row['id'].'"/>';
            $rRow[] = $row['param_url'];
            $rRow[] = $row['name'];
            $rRow[] = $row['redirect_url'];
            $rRow[] = $row['exception_url'];
            $rRow[] = '<a target="_blank" href="index.php?cont=campaign&act=index&id=' . $row['id'] . '" class="edit" >edit</a>';
            $rRow[] = '<a onclick=setRole("article","' . $row['id'] . '"); >article</a> | '.
                    '<a onclick=setRole("placeholder","' . $row['id'] . '"); >placeholder</a>';            
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();        
    }
    
    public function ipsforblacklistsAction()
    {
        $siteModel = new Site(); 
        $sData = $siteModel->getSites(); 
        
        $this->render('ipsforblacklists', array(
            'sites'=>$sData,
        ));
    }
    
    public function missingurlsAction()
    {
        $data = array();
        
        $this->render('missingurls', array(
            'data'=>$data,
        ));
    }
    
    public function missinglistAction()
    {
        $urlsModel = new Urls();
        //$result = $urlsModel->getUrlsDataByCustomClause('redirect_url = \'\' OR exception_url = \'\'');
        $result = $urlsModel->getAdvWithMissingUrl();
        
        $resArr = array();
        foreach($result as $row) {
            
            $rRow = array();
            $rRow[] = '<input type="checkbox" name="check_adv[]" _id="'.$row['id'].'"/>';
            $rRow[] = $row['param_url'];
            $rRow[] = $row['name'];
            $rRow[] = empty($row['redirect_url']) ? 'Missing' : $row['redirect_url'];
            $rRow[] = empty($row['exception_url']) ? 'Missing' : $row['exception_url'];
            $rRow[] = '<a target="_blank" href="index.php?cont=campaign&act=index&id=' . $row['id'] . '" class="edit" >edit</a>';
            $rRow[] = '<a onclick=setRole("article","' . $row['id'] . '"); >article</a> | '.
                    '<a onclick=setRole("placeholder","' . $row['id'] . '"); >placeholder</a>';
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();        
    }
    
    public function setroleAction()
    {
        set_time_limit(0);
        $role = isset($_POST['role'])&&!empty($_POST['role']) ? $_POST['role'] : false; 
        $urls_id = isset($_POST['urls_id'])&&!empty($_POST['urls_id']) ? $_POST['urls_id'] : false; 
        if($role && $urls_id) {
            
            $urlid_array = explode(",",$urls_id);
            foreach ($urlid_array as $url_id) {
                //get role id 
                $roleModel = new Roles(); 
                $rData = $roleModel->getDataByFields(array('type'=>$role)); 
                $role_id = $rData['id']; 

                $data = array('role_id'=>$role_id); 
                $whereFields = array('urls_id' => $url_id);

                $advRoleModel = new AdvertiserRoles(); 
                $advRoleModel->updateData($data, $whereFields);

                // set role in the remote wp site 
                $result = WpAdmin::setRoleByRpcApi($url_id, $role);
            }
        }
        echo json_encode(array('Done'));
        exit();                
    }
    
    public function duplicationAction() 
    {        
        $checksite = (isset($_REQUEST['checksite']) && !empty($_REQUEST['checksite'])) ? $_REQUEST['checksite'] : false;    
        $log_name = (isset($_REQUEST['log_name']) && !empty($_REQUEST['log_name'])) ? $_REQUEST['log_name'] : false;
        $delete = (isset($_REQUEST['delete'])) ? true : false;
        
        $data = array();
        $showLogsList = false; 
        $showLogData = false; 
        
        if($checksite || $delete) {
            
            $showLogsList = true; 
            $dir = dirname(__FILE__).'/../logs/';
            
            if($delete && $log_name) {
                @unlink($dir.$log_name); 
            }
            
            $dh = opendir($dir);
            while($filename = readdir($dh)) {
                if(strpos($filename, 'TroubleshootingController')) {
                   $data[] =  $filename; 
                }
            }
        } 
        else if($log_name && !$delete) {
            
            $showLogData = true;
            $file = dirname(__FILE__).'/../logs/'.$log_name;
            
            // Read a file by-line 
            $handle = fopen ($file, "r");
            
            $block = false;
            $rootUrl = false;
            
            while (!feof ($handle)) {                    
                
                $buffer = fgets($handle, 4096);
                if(strpos($buffer,'started at')) $block = true; 
                else if(strpos($buffer,'ended at')) $block = false;                 
                
                if($block) {
                    
                    if(strpos($buffer,'Root url')) {
                        
                        $bData = explode('Root url:', $buffer);
                        $dUrl = isset($bData[1]) ? parse_url(trim($bData[1])) : false;
                        $rootUrl = ($dUrl) ? $dUrl['scheme'].'://'.$dUrl['host'] : false;
                    } 
                    else {
                        
                        preg_match_all("|from:(.*)\]|sUSi", $buffer, $wpData);
                        // get mini-site link 
                        $miniSiteLink = isset($wpData[1][0]) ? trim($wpData[1][0]) : false;
                        
                        // get mini-site 
                        $miniSite = false;
                        if($miniSiteLink) {
                            if(filter_var($miniSiteLink, FILTER_VALIDATE_URL)) {
                               $urlData = parse_url($miniSiteLink); 
                               $miniSite = $urlData['scheme'].'://'.$urlData['host']; 
                            }   
                        }
                        
                        // get title, get remote link and updated remote link
                        $remoteData = explode(']:',$buffer);
                        $title = isset($remoteData[1]) ? trim(strip_tags($remoteData[1])) : false; 
                        
                        $remoteLink = false;
                        if(isset($remoteData[1])) {
                            preg_match_all("|href=\"(.*)\"\>|sUSi", $remoteData[1], $linkData);
                            if(isset($linkData[1][0])) {
                                $remoteLink = $rootUrl.$linkData[1][0]; 
                            }
                            
                            $data[] = array(
                                    'wpsite'=>$miniSite, 
                                    'title' => $title,
                                    'miniSiteLink' => $miniSiteLink, 
                                    'remoteLink'=> $remoteLink);                            
                        }
                    }
                }
                else {
                        $data[] = array(
                                'wpsite'=> ' - ', 
                                'title' => ' - ',
                                'miniSiteLink' => ' - ', 
                                'remoteLink'=> ' - ');
                }
            }
        }
        
        // check scan progress 
        $inProgress = false; 
        $dir = dirname(__FILE__).'/../';
        $dh = opendir($dir); 
        while($filename = readdir($dh)) {
            if($filename  == 'run_scan') {
                $inProgress = true;
            }
        }        
        
        $this->render('duplication', array(
            'data'=>$data,
            'showLogsList' => $showLogsList, 
            'showLogData' => $showLogData, 
            'scanProgress' => $inProgress, 
        ));        
    }
    
    /**
     * heliumscanAction
     * @desc (run outer heliumscan script)
     */
    public function runscanAction()
    {
        //$exec = system('php heliumscan.php', $output);
        $presid = proc_open('nohup php heliumscan.php &', array(), $x);
        proc_close($presid);
        die('run'); 
    }
    
    public function blacklogosAction() 
    {
        
        $data = array(); 
        
        $sql = 'SELECT bl.url_id,site.domain,urls.name,wp_post_id FROM black_logos bl JOIN site ON bl.site_id=site.id JOIN urls ON bl.url_id=urls.id';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute();
        $data = $smtm->fetchAll();
        
        $this->render('blacklogos', array(
            'data'=>$data,
        ));        
    }
    
}

?>

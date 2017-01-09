<?php

class SiteController extends BaseController {

    function indexAction() 
    {    
        $this->registerScriptFile(BASE_URL.'js/app.sites.js');
        
        // treeview plugin 
        $this->registerStyleSheetFile(BASE_URL.'css/jquery.treeview.css');
        $this->registerScriptFile(BASE_URL.'js/jquery.cookie.js');
        $this->registerScriptFile(BASE_URL.'js/jquery.treeview.js');
        $this->registerScriptFile(BASE_URL.'js/partials.editcategory.js');
        
        $countryCodes = $this->getCountryCodes();
        $openDialogId = 0;
        $permalinkData = array(); 
        $siteModel = new Site(); 
        
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && $_REQUEST['id'] > 0) {
            
            $openDialogId = (int) $_REQUEST['id'];
            $siteData = $siteModel->getSites($site_id);

            $domain = $siteData['domain'];
            
            $permalinkData = array(
                '' => 'http://'.$domain.'/?p=123', 
                '/%year%/%monthnum%/%day%/%postname%/' => 'http://'.$domain.'/2012/10/05/sample-post/', 
                '/%year%/%monthnum%/%postname%/' => 'http://'.$domain.'/2012/10/sample-post/', 
                '/archives/%post_id%' => 'http://'.$domain.'/archives/123', 
                '/%postname%/' => 'http://'.$domain.'/sample-post/', 
            ); 
        }
        
        $blacklistModel = new Blacklists(); 
        $blacklistData = $blacklistModel->getDataByFields(array('type'=>'private'), true);
        $projects = app::inst()->db->query("SELECT * FROM projects")->fetchAll();

        $this->render('index', array(
            'openDialogId'=>$openDialogId,
            'countryCodes'=>$countryCodes,
            'permalinkData'=>$permalinkData,
            'blacklistData'=>$blacklistData,
            'projects'=>$projects,
        ));
    }
    
    public function listAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $siteModel = new Site(); 
        $result = $siteModel->getDataByFields(array(), true, 
                                    array('country', 'domain'), 
                                    array('id', 'domain', 'country','status','curl_status','check_advs'));
        
        $resArr = array();
        foreach($result as $row){
            $rRow = array();
            $rRow[] = $row['domain'];
            $rRow[] = ($row['status']==1) ? '<div title="Verified Clean" class="green_circle">&nbsp;</div>' : 
                '<div title="Some problem (view details)" class="red_circle">&nbsp;</div>&nbsp;<a href="http://sitecheck.sucuri.net/results/'.$row['domain'].'">details</a>';
            
            $rRow[] = ($row['curl_status']==1) ? '<div title="Curl in order" class="green_circle">&nbsp;</div>' : 
                '<div title="Some problem (view CurlChecker log for details)" class="red_circle">&nbsp;</div>';
            
            // fetching posts button 
            $rRow[] = '<a href="#" site_id="' . $row['id'] . '" class="edit" title="Resave all post." onclick="/*resave_posts(this);*/ return false;">resave posts</a>';
            
            $rRow[] = '<a href="#" site_id="' . $row['id'] . '" class="edit" title="fetching all advertisers from minisite." onclick="/*fetching_posts(this);*/ return false;">fetch posts</a> | <a href="#" site_id="' . $row['id'] . '" class="edit" title="fetching all categories from minisite." onclick="/*fetching_cat(this);*/ return false;">fetch categories</a> | <a href="#" url_id="' . $row['id'] . '" class="edit" onclick="/*open_edit(this);*/ return false;">edit</a> <a href="#" url_id="' . $row['id'] . '" class="delete" onclick="/*open_delete(this);*/ return false;">delete</a>';
            
            //$rRow[] = ($row['hide_advs']==0) ? '<span>Shown</span> <a href="javascript:void(0)" site_id="'.$row['id'].'" class="show_hide_advs" onclick="show_hide_advs(this)">Hide</a>' : '<span>Hidden</span> <a href="#" site_id="'.$row['id'].'" class="show_hide_advs" onclick="show_hide_advs(this)">Show</a>';
            
            $rRow[] = '<input type="checkbox" site_id="'.$row['id'].'" onclick="/*set_check_advs(this)*/"'.(($row['check_advs']==1) ? ' checked' : '').' />';
            
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }
    
    public function saveAction() 
    {
        Log::init('SiteController');
        Log::start('saveAction');
        
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $siteModel = new Site();
        
        if ($_POST) {
            
            // set logo data 
            $logoData = array(
                'logo_width' => $_POST['logo_width'],
                'logo_height' => $_POST['logo_height']
            );
            $_POST['logo_data'] = serialize($logoData);
            
            // set ftp data 
            $port = !empty($_POST['ftp_port']) ? $_POST['ftp_port'] : 21; 
            $ftpData = array(
                'ftp_host' => $_POST['ftp_host'],
                'ftp_port' => $port,
                'ftp_login' => $_POST['ftp_login'],                
                'ftp_pass' => $_POST['ftp_pass'],
                'ftp_path' => $_POST['ftp_path'],
                'ftp_path_cforder' => $_POST['ftp_path_cforder']
            );
            $_POST['ftp_data'] = serialize($ftpData);
            
            $siteModel->attributes = $_POST;

            $siteModel->show_iframe = isset($_POST['show_iframe']) ? 1 : 0;
            
            Log::l('Passed data: '. json_encode($_POST), Zend_Log::INFO);
            
            if (!$siteModel->validate()) {
                
                Log::l('The data has not been validated: '.$siteModel->getErrors(), Zend_Log::ERR);
                Log::end();
                echo json_encode(array('errors' => $siteModel->getErrors()));
                exit();
            }

            // add or update site by model method 
            $site_id = false; 
            
            if(intval($_POST['id'])) {
                
                $siteModel->updateSite($_POST['id']);
                $site_id = $_POST['id']; 
                Log::l('Data was updated by site_id: '. $site_id, Zend_Log::INFO);
            } 
            else {
                
                $siteModel->addSite(); 
                Log::l('Data was added succefully.', Zend_Log::INFO);
                // get site id 
                $siteData = $siteModel->getDataByFields(array('domain'=>$_POST['domain']));
                
                // get all categories from remote site
                if(isset($siteData['id']) && !empty($siteData['id'])) {
                    
                    Log::l('Try to get categories data from remote server['.$_POST['domain'].'] and save received categories to local db.', Zend_Log::INFO);
                    //$this->setPostsFromRemoteSite($siteData['id']);
                    WpAdmin::fillCategories($siteData['id']);
                    $site_id = $siteData['id'];
                }
            }
            
            //save black list 
            WpAdmin::saveSiteBlacklist($_POST['blacklist_id'], $site_id);
            //save Show_Iframe Param
            $redirectApiObj = new RedirectAPI();

            //die($option_value);
            $response = $redirectApiObj->setShowIFrame($site_id, $siteModel->show_iframe);
            
        }

        Log::end();
        echo json_encode("saved");
        exit();
    }

    function getinfoAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $response = array();
        if (isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
            
            $siteModel = new Site();
            $response = $siteModel->getSites($_REQUEST['id']); 
            
            // get width and height logo img 
            $logoData = unserialize($response['logo_data']); 
            
            $response['logo_width'] = (isset($logoData['logo_width'])) ? $logoData['logo_width'] : '';
            $response['logo_height'] = (isset($logoData['logo_height'])) ? $logoData['logo_height'] : '';
            
            // get ftp data
            $ftpData = unserialize($response['ftp_data']);
            
            $response['ftp_host'] = isset($ftpData['ftp_host']) ? $ftpData['ftp_host'] : '';
            $response['ftp_port'] = isset($ftpData['ftp_port']) ? $ftpData['ftp_port'] : '';
            $response['ftp_login'] = isset($ftpData['ftp_login']) ? $ftpData['ftp_login'] : '';
            $response['ftp_pass'] = isset($ftpData['ftp_pass']) ? $ftpData['ftp_pass'] : '';
            $response['ftp_path'] = isset($ftpData['ftp_path']) ? $ftpData['ftp_path'] : '';
            $response['ftp_path_cforder'] = isset($ftpData['ftp_path_cforder']) ? $ftpData['ftp_path_cforder'] : '';
            
            // get magic parameter 
            $magicParam = false; 
            
            $site_id = $_REQUEST['id']; 
            
            $redirectApiObj = new RedirectAPI();
            $magicParam = $redirectApiObj->getMagicPatam($site_id);    
            $magicParam2 = $redirectApiObj->getMagicPatam($site_id, true);
            
            $response['magic2'] = $magicParam2; 
            $response['magic'] = $magicParam; 
            // end get magic parameter 
            
            // get permalink 
            $permaLink = $redirectApiObj->getPermaLink($site_id);
            $response['permalink_struct'] = $permaLink;
            
            // get blacklist_id if exist 
            $sbModel = new SiteBlacklist(); 
            $bData = $sbModel->getDataByFields(array('site_id'=>$_REQUEST['id'])); 
            $response['blacklist_id'] = !empty($bData['blacklist_id']) ? $bData['blacklist_id'] : '';
        } 
        
        echo json_encode($response);
        exit();
    }
    
    public function fetchingpostsAction() 
    {
        $response = array('status' => 'Done');
        
        if(isset($_REQUEST['site_id']) && 
                !empty($_REQUEST['site_id'])) {   
            
            // pre fetching categories for it site 
            WpAdmin::fillCategories($_REQUEST['site_id']);
            // fetching posts 
            $this->setPostsFromRemoteSite($_REQUEST['site_id']);
            // assign it post with categories
            WpAdmin::assignAllUrlsToCategories($_REQUEST['site_id']);
        }
        echo json_encode($response);
        exit();
    }

    public function fetchingcategoriesAction()
    {
        $response = array('status' => 'Done');
        
        if(isset($_REQUEST['site_id']) && 
                !empty($_REQUEST['site_id'])) {   
            
            WpAdmin::fillCategories($_REQUEST['site_id']);
        }
        echo json_encode($response);
        exit();        
    }

    public function setpermalinkAction()
    {   
        $response = '';
        
        if(isset($_POST['site_id'])) {
            
            $site_id = (!empty($_POST['site_id'])) ? $_POST['site_id'] : false; 
            $permalink_struct = (!empty($_POST['permalink_struct'])) ? $_POST['permalink_struct'] : false; 
            
            if($site_id) {
                
                $redirectApiObj = new RedirectAPI();
                $response = $redirectApiObj->setPermaLink($site_id, $permalink_struct);
                // update parametrize url to local db 
                WpAdmin::updateParamUrl($site_id);
            }
        }
        
        echo $response; 
        exit;
    }
    
    /** 
     * use redirectApi for get all Post IDS from remote wp site
     * @param int site_id
     */    
    
    private function setPostsFromRemoteSite($site_id) 
    {
        $objXMLRP = WpAdmin::getXMLRPobj($site_id); 
        
        // get all isset post_ids 
        $post_ids = array();

        $urlsModel = new Urls();
        $urlsData = $urlsModel->getDataByFields(array('site_id' => $site_id), true, array(), array('wp_post_id'));
        foreach($urlsData as $row)
            $post_ids[] = $row['wp_post_id'];

        $post_ids = array_unique($post_ids);
        // end get all isset post_ids         
        
        // get all post ids from redirectApi
        $redirectApi = new RedirectAPI(); 
        
        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];
        
        $postIDs = $redirectApi->getPostIDs($domain, $wp_login, $wp_pass);
        
        // end get all post ids from redirectApi
        //die('seconds');
        if(isset($postIDs['result']) &&
                $postIDs['result'] != 'none') {
            
            $result = array(); 
            $result = array_diff($postIDs['result'], $post_ids);
            
            if(!empty($result))
                foreach($result as $post_id) {

                    if(!in_array($post_id, $post_ids)) {

                        $getPostData = $objXMLRP->getPost($post_id);
                        //$getPostData = $objXMLRP->get_post($post_id);
                        $rObj = simplexml_load_string($getPostData);

                        if(isset($rObj->fault)) continue; 
                        elseif(isset($rObj->params->param->value)) {

                            $itemObj = $rObj->params->param->value; 

                            $memberObj = (array)$itemObj->struct;
                            $feed_item = $memberObj['member'];                   

                            $this->addPostFromFeed($feed_item, $site_id);                    
                        }
                    }
                }
        }
        
        return true; 
    }
    
    private function addPostFromFeed($feed_item, $site_id)
    {
	$name = false;
	$wp_post_id = false;
	$desc_logo = 0;
	$description = false;
	$country = false;
	$start = false;
	$end = false;
	$ips = false;
	$status = 'saved';
	$site_category = false;
        
	$redirect_url = false;
	$exception_url = false;
	$param_url = false;
        
	$desc_logo_url = false;
        
        $post_link = false; 
        
        $role = 'article';
        
        foreach($feed_item as $option_item) {
            
            if($option_item->name == 'post_id')
                $wp_post_id = (int)$option_item->value->string;
            
            if($option_item->name == 'post_title')
                $name = (string)$option_item->value->string;
            
            if($option_item->name == 'post_content') {
                
                $content = (string)$option_item->value->string;
                $description = strip_tags($content);
                
                if($content != $description) {
                    $desc_logo = 1; 
                }  
            }   
            
            if($option_item->name == 'link')
               $post_link = (string)$option_item->value->string;
            
            // get categories 
            if($option_item->name == 'terms') {
                
               $categories = array(); 
               
               $categoriesData = isset($option_item->value->array->data->value) ? 
                                    $option_item->value->array->data->value : false;
               
               if(!empty($categoriesData)) {
                   
                   foreach ($categoriesData as $categoyItemArr) {
                       
                       $categoyItemArr = (array)$categoyItemArr;
                        foreach ($categoyItemArr['struct'] as $categoyItem) {

                            if($categoyItem->name == 'name')
                                $categories[] = (string)$categoyItem->value->string; 
                        }   
                   }
               }
               
               $site_category = serialize($categories);
            }
            
            $key_name = false; 
            
            if($option_item->name == 'custom_fields') {
                
               $cfData = isset($option_item->value->array->data) ? (array)$option_item->value->array->data : false;  
               if(!empty($cfData) && isset($cfData['value'])) {
                   
                   foreach($cfData['value'] as $cfItem) {

                        $cfItem = (array)$cfItem;
                        if(isset($cfItem['member'])) {
                            foreach($cfItem['member'] as $cfOption) {
                                if($cfOption->name == 'key') 
                                    $key_name = (string)$cfOption->value->string;                                     

                                if($cfOption->name == 'value') {

                                    if($key_name == 'exception_url')
                                        $exception_url = (string)$cfOption->value->string;
                                    elseif ($key_name == 'link')
                                        $redirect_url = (string)$cfOption->value->string;
                                    elseif ($key_name == 'role')
                                        $role = (string)$cfOption->value->string;

                                }
                            }
                        }
                   }
               }
            }
            
            if($option_item->name == 'post_thumbnail') {
                
                $thumbnailData = @$option_item->value; 
                $thumbnailItems = @(array)$thumbnailData->struct; 
                
                if(isset($thumbnailItems['member'])) {
                    
                    foreach($thumbnailItems['member'] as $thumbItem) {
                       
                        if($thumbItem->name == 'thumbnail')
                            $desc_logo_url = (string)$thumbItem->value->string;
                    }
                } 
            }
        }
        
        if(!empty($post_link)) {
            
            $redirectApiObj = new RedirectAPI();
            $magicParam = $redirectApiObj->getMagicPatam($site_id);        
            $param_url =  $post_link.'&'.$magicParam;
        }
        
        //echo $name." - ".$role.PHP_EOL;
        
        $rolesModel = new Roles();
        $roles = $rolesModel->getDataByFields(array(),true);
        //var_dump($roles);
        $role_id = 1;
        foreach ($roles as $role_item) {
            if ($role_item['type']==$role) $role_id = $role_item['id'];
        }
        //echo "role_id = ".$role_id.PHP_EOL;
        //exit;
        
        // added to db         
        $urlsModel = new Urls(); 
        $urlsModel->insertData(array(
            'name' => $name,
            'redirect_url' => $redirect_url,
            'exception_url' => $exception_url,
            'ips' => $ips,
            'country' => $country,
            'start' => $start,
            'end' => $end,
            'description' => $description,
            'site_category' => $site_category,
            'site_id' => $site_id,
            'wp_post_id' => $wp_post_id,
            'desc_logo' => $desc_logo,
            'desc_logo_url' => $desc_logo_url,
            'param_url' => $param_url, 
            'status' => "saved"
        ));
        
        $advertiser_id = app::inst()->db->lastInsertId();

        $AdvertiserRoles = new AdvertiserRoles();
        $AdvertiserRoles->insertData(array(
            'urls_id' => $advertiser_id,
            'role_id' => $role_id
        ));
        if($desc_logo_url) {
            
            $filename = $advertiser_id.'.jpg';
            $savefolder = dirname(__FILE__).'/../../upload/'.$site_id; 
            
            // make upload dir 
            @mkdir($savefolder, 0777, true);                        
            
            $file = $savefolder.'/'.$filename;
            
            // Open the file to get existing content
            $file_content = file_get_contents($desc_logo_url);

            // Write the contents back into the file
            file_put_contents($file, $file_content);
        }
        
        return true;
    }
    
    public function setmagicAction () 
    {
        $response = array();
        
        $mp2 = (isset($_POST['mp2']) && $_POST['mp2'] == 'true') ? true : false; 
            
        if(isset($_POST['site_id'])) {
            
            $site_id = (!empty($_POST['site_id'])) ? $_POST['site_id'] : false; 
            $option_value = (!empty($_POST['option_value'])) ? $_POST['option_value'] : false; 
        
            if($site_id &&
                $option_value) {
                
                $redirectApiObj = new RedirectAPI();
                $response = $redirectApiObj->setMagicParam($site_id, $option_value, $mp2);
                
                if(!$mp2) {
                    // update parametrize url to local db 
                    WpAdmin::updateParamUrl($site_id);              
                }
            }
        }
        
        echo $response;
        exit();
    }
    
    public function delcategoryAction () 
    {
        if(isset($_POST['category_id'])) {
            
            $site_id = $_POST['site_id'];
            $category_id = (!empty($_POST['category_id'])) ? $_POST['category_id'] : false; 
        
            if($category_id &&
                    $site_id) {
                
                $objXMLRP = WpAdmin::getXMLRPobj($site_id);
                $getDelCatData = $objXMLRP->deleteCategory($category_id);
                $rObj = simplexml_load_string($getDelCatData);
                
                $isMove = isset($rObj->params->param->value->boolean) ? 
                                    $rObj->params->param->value->boolean : 
                                        false; 
                
                //delete category from local db 
                $catModel = new Categories(); 
                $catModel->deleteCategoryByID($category_id, $site_id);
                
                if($isMove) {
                    
                    $response = "deleted";
                }
                else {
                    
                    $errors['post_errors'] = 'Error: Category with id = '.$category_id.' was not removed.';
                    echo json_encode(array('errors' => $errors));
                    exit();
                }
            }
        }
        
        echo json_encode($response);
        exit();        
    }

    function savecategoryAction() 
    {
        Log::init('SiteController');
        Log::start('savecategoryAction');
        if(isset($_POST['old_name']) && isset($_POST['new_name']) && isset($_POST['site_id'])) {
            
            $site_id = $_POST['site_id'];
            $old_name = (!empty($_POST['old_name'])) ? str_replace('&', '&amp;', $_POST['old_name']) : false;
            $new_name = (!empty($_POST['new_name'])) ? str_replace('&', '&amp;', $_POST['new_name']) : false;
        
            $old_name = str_replace('&amp;amp;','&amp;',$old_name);
            $new_name = str_replace('&amp;amp;','&amp;',$new_name);
            
            if($old_name && $new_name && $site_id) {
                
                $slug = '';
                
                $siteModel = new Site();
                $sData = $siteModel->getSites($site_id);
                Log::l('Saved category in site:'.$sData['domain']);

                $catModel = new Categories();
                $catModel->renameCategory($old_name, $new_name, $site_id);
                $urlsModel = new Urls();
                $advs = $urlsModel->getTableData($site_id);
                foreach ($advs as $adv) {
                    $data = array();
                    if (stristr($adv['site_category'],$old_name)) {
                        $data['site_category'] = str_replace($old_name,$new_name,$adv['site_category']);
                    }
                    if (stristr($adv['categories_tree'],$old_name)) {
                        $data['categories_tree'] = str_replace($old_name,$new_name,$adv['categories_tree']);
                    }
                    if (!empty($data)) {
                        $data['id'] = $adv['id'];
                        $urlsModel->updateData($data);
                    }
                }
                $response = "saved";
            }
        }
        
        Log::end();
        echo json_encode($response);
        exit();         
    }
    
    function createcategoryAction() 
    {
        Log::init('SiteController');
        Log::start('createcategoryAction');
        if(isset($_POST['category_name'])) {
            
            $site_id = $_POST['site_id'];
            $category_name = (!empty($_POST['category_name'])) ? str_replace('&', '&amp;', $_POST['category_name']) : false;
        
            if($category_name &&
                    $site_id) {
                
                $objXMLRP = WpAdmin::getXMLRPobj($site_id);
                
                $slug = '';
                $description = '';
                $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;
                
                $siteModel = new Site(); 
                $sData = $siteModel->getSites($site_id);
                Log::l('Added category for site:'.$sData['domain']);
                Log::l('Try to create category by RPC API with data (use $objXMLRP->newCategory()): '.  json_encode(array(
                                                                                'category_name'=>$category_name,
                                                                                'parent_id'=>$parent_id,
                                                                                'description'=>$description,
                                                                                'slug'=>$slug,
                                                                    )));
                $getCreateCatData = $objXMLRP->newCategory($category_name, $slug, $description, $parent_id);
                $rObj = simplexml_load_string($getCreateCatData);
                
                $category_id = ((isset($rObj->params->param->value->int)) ? $rObj->params->param->value->int :
                                        ((isset($rObj->params->param->value->string)) ? $rObj->params->param->value->string : false));
                
                Log::l('Reponse of $objXMLRP->newCategory() is: '.  json_encode($rObj));
                Log::l('Category ID is ['.$category_id.']');
                
                if($category_id) {
                    
                    $cat_id = (int)$category_id;
                    $redirectApiObj = new RedirectAPI();
                    Log::l('Try to set category with id=['.$cat_id.'] via Redirect Api');
                    $res = $redirectApiObj->setNavmenuCategory($site_id, $cat_id);
                    Log::l('Result of setNavmenuCategory by redirect api: '.$res);
                    
                    // added new category to local db 
                    $catModel = new Categories();
                    $catModel->addCategory($category_id, $category_name, $parent_id, $site_id); 
                    
                    $response = "created";
                }
                else {
                    
                    $errors['post_errors'] = 'Error: Category with name = '.$category_name.' was not created.';
                    Log::end();
                    echo json_encode(array('errors' => $errors));
                    exit();
                }
            }
        }
        
        Log::end();
        echo json_encode($response);
        exit();         
    }
    
    function deleteAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {
            
            $siteModel = new Site(); 
            $siteModel->deleteByID($_REQUEST['id']); 
            
            $urlsModel = new Urls(); 
            $urlsModel->deleteAllDataBySiteID($_REQUEST['id']);
        }
        
        echo json_encode('ok');
        exit();
    }
    
    public function resavepostsAction()
    {
        Log::init('RESAVEPOST_LOG');
        Log::start('resavepostsAction');
        
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
        
        if(!empty($site_id)) {
            
            // init wp api obj
            $objXMLRP = WpAdmin::getXMLRPobj($site_id);
            
            $siteModel = new Site();
            $urlsModel = new Urls(); //'id'=>873
            $uData = $urlsModel->getDataByFields(array('site_id'=>$site_id), true, array('name'));
            
            $cnt=1;
            foreach ($uData as $uRow) {
                
                if($uRow['wp_post_id']) {
                    
                    $advertiser_id = $uRow['id'];
                    $localWpPostID = (int)$uRow['wp_post_id'];
                    
                    $title = $uRow['name'];
                    $description = $uRow['description'];
                    $logoWPurl = $uRow['desc_logo_url'];
                    $put_desc_logo = ($uRow['desc_logo']==1) ? true : false;
                    
                    $site_category = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $uRow['site_category']);
                    $category = @unserialize($site_category);
                    
                    $keywords = strtolower($uRow['name']);

                    $body = WpAdmin::concatLogoToDesc($logoWPurl, $description, $advertiser_id, $site_id, $put_desc_logo);
                    $excerptDesc = WpAdmin::getFirstSentence($description);

                    // if isset value of wp_post_id excute "edit_post"
                    $descriptionLogo = $uRow['desc_logo_url'];
                    $redirectUrl = $uRow['redirect_url'];
                    $exceptionUrl = $uRow['exception_url'];
                    $exceptionUrl2 = $uRow['exception_url2'];

                    $sData = $siteModel->getSites($site_id);
                    $emailParam = $sData['email'];
                    
                    // get role type: 
                    $role = 'advertiser';
                    $rolesModel = new Roles(); 
                    $advRolesModel = new AdvertiserRoles(); 
                    $arData = $advRolesModel->getDataByFields(array('urls_id'=>$advertiser_id)); 
                    $role_id = isset($arData['role_id']) ? $arData['role_id'] : false; 
                    if($role_id) {
                        $rData = $rolesModel->getDataByFields(array('id'=>$role_id));
                        $role = $rData['type'];
                    }
                    
                    $customfields_edit = WpAdmin::getEditCustomFields($site_id, $localWpPostID, $emailParam, $redirectUrl, $exceptionUrl, $exceptionUrl2, $descriptionLogo, $role);
                    
                    // send exist image by rpc and resend that received mediaId 
                    $logoData = WpAdmin::sendLogoToWpByRpc($site_id, $advertiser_id);
                    $wp_post_thumbnail = isset($logoData['id'])&&!empty($logoData['id']) ? $logoData['id'] : false;
                    
                    // set featured_post
                    $wp_page_order = ($uRow['featured_post']!=0) ? 0 : 2147483647;
                    
                    // resave process
                    Log::l('Number of processed post is : '.$cnt);
                    Log::l('Processed of advertiser with wp_post_id = '.$localWpPostID);
                    
                    $postData = $objXMLRP->getPost($localWpPostID);
                    $rObj = simplexml_load_string($postData);
                    
                    $cnt++;
                    if(is_object($rObj)) {

                        $chPostExist = isset($rObj->params->param->value->struct->member[0]->value->string) ? 
                                                (int)$rObj->params->param->value->struct->member[0]->value->string :
                                                    false;
                        Log::l('$localWpPostID = '.$localWpPostID.'; $chPostExist = '.$chPostExist);
                        if($localWpPostID == $chPostExist) {
                            // resend post
                            $response = $objXMLRP->edit_post($localWpPostID, $title, $body, $category, $keywords, $customfields_edit, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
                            $rObj = simplexml_load_string($response);
                            Log::l('"edit_post" by RPC : '.json_encode($rObj));
                        } 
                        else {
                            // added post if not exist 
                            $response = $objXMLRP->create_post($title, $body, $category, $keywords, $customfields_edit, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
                            $rObj = simplexml_load_string($response);
                            Log::l('"create_post" by RPC : '.json_encode($rObj));
                            
                                $_wp_post_id = isset($rObj->params->param->value->string) ? 
                                                        (int)$rObj->params->param->value->string :
                                                            false;

                                if(intval($_wp_post_id))
                                    $urlsModel->updateData(array('wp_post_id'=>$_wp_post_id, 'id'=>$advertiser_id));
                                else continue;
                        }
                    }
                    else continue;  
                }
            }            
        }
        
        Log::end();
        
        echo json_encode(array('status'=>'Done')); 
        exit(); 
    }      
    
    public function updatepluginAction() 
    {
        // get plagin type 
        $type = (isset($_REQUEST['ftp_path_cforder']) && $_REQUEST['ftp_path_cforder'] == 1) ? 'cfOrder' : 'redirectExtend' ;
        
        
        $output = array();    
        // get client plugin 
        $file = isset($_FILES['plugin']['name']) ? $_FILES['plugin']['name'] : false;
        
        if($file) {
            $siteModel = new Site(); 
            $sData = $siteModel->getDataByFields(array(), true);
            //$sData = $siteModel->getDataByFields(array('id'=>4), true);
            //$sData = app::inst()->db->query("SELECT * FROM site WHERE id=25 OR id=152")->fetchAll();
            foreach($sData as $key => $sItem) {

                $output[$key]['ftp_host'] = '- not available -';
                $output[$key]['ftp_login'] = ' - not available - ';
                $output[$key]['ftp_pass'] = ' - not available - ';            
                $output[$key]['status'] = 'none'; 
                $output[$key]['description'] = 'none';

                $output[$key]['domain'] = $sItem['domain']; 
                //if($sItem['domain']!='go-shop-online.com') continue;

                $ftpData = unserialize($sItem['ftp_data']);

                $ftp_host = isset($ftpData['ftp_host']) ? (string)$ftpData['ftp_host'] : '';
                $ftp_port = isset($ftpData['ftp_port']) ? (string)$ftpData['ftp_port'] : '';
                $ftp_login = isset($ftpData['ftp_login']) ? (string)$ftpData['ftp_login'] : '';
                $ftp_pass = isset($ftpData['ftp_pass']) ? (string)$ftpData['ftp_pass'] : '';
                $ftp_path = isset($ftpData['ftp_path']) ? (string)$ftpData['ftp_path'] : '';
                $ftp_path_cforder = isset($ftpData['ftp_path_cforder']) ? (string)$ftpData['ftp_path_cforder'] : '';

                if($ftp_host && 
                        $ftp_port &&
                            $ftp_login &&
                                $ftp_pass) {

                    $output[$key]['ftp_host'] = $ftp_host.':'.$ftp_port.'/';
                    $output[$key]['ftp_login'] = $ftp_login;
                    $output[$key]['ftp_pass'] = $ftp_pass;

                    //process of uploading plugin file to remote site
                        $connect = ftp_connect($ftp_host); //, $ftp_port
                        if(!$connect) {

                            $output[$key]['status'] = 'Fail!'; 
                            $output[$key]['description'] = 'Error:"Connect is down!"'; 
                        } 
                        else {

                            $result = @ftp_login($connect, $ftp_login, $ftp_pass);
                            if ($result==false) {

                                $output[$key]['status'] = 'Fail!'; 
                                $output[$key]['description'] = 'Error:"Ftp pass or login incorrect!"';
                            }
                            else {
                                
                                $filename = 'index.php'; 
                                $cDir = dirname(__FILE__);                                    

                                // if exist client plugin use that else use local subversion plugin file
                                if($type == 'redirectExtend') {

                                    $tmp = ($file) ? $_FILES['plugin']['tmp_name'] : $cDir.'/../../data/redirectExtend/'.$filename;
                                    $path = $ftp_path;
                                } 
                                else if($type == 'cfOrder') {

                                    $tmp = ($file) ? $_FILES['plugin']['tmp_name'] : $cDir.'/../../data/cforder/'.$filename;

                                    $path = $ftp_path_cforder;
                                }

                                if(!$path) {

                                    $output[$key]['status'] = 'Fail!'; 
                                    $output[$key]['description'] = 'Error:"Unknown plugin path"';                                                
                                } 
                                else if (ftp_chdir($connect, $path)) {

                                    try{                                            
                                        ftp_put($connect, $filename, $tmp, FTP_BINARY);

                                        // end of process 
                                        $output[$key]['status'] = 'Ok!'; 
                                        $output[$key]['description'] = 'Process is done!';   
                                    }
                                    catch(Exception $e){

                                        $output[$key]['status'] = 'Fail!'; 
                                        $output[$key]['description'] = $e->getMessage(); 
                                    }                                    
                                }
                                else {

                                    $output[$key]['status'] = 'Fail!'; 
                                    $output[$key]['description'] = 'Error:"Can\'t change directory throught ftp access"';                                
                                }
                            }
                        }
                }
                else {
                    $output[$key]['status'] = 'Fail!'; 
                    $output[$key]['description'] = 'Ftp data is empty! ftp_data is: ['.  json_encode($ftpData).']'; 
                }

                ftp_quit($connect);
            }
        }
        $this->render('updateplugin', array(
            'upData'=>$output
        ));	        
    }
    
    public function settingsAction() 
    {    
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');        
        
        $response = array(); 
        $settingsModel = new Settings();
        
        if (isset($_REQUEST['set']) && 
                $_REQUEST['set'] == 'set') {
            
            // get errors (validation)
            $errors  = array(); 
            // email validation
            if(!filter_var($_REQUEST['admin_email'], FILTER_VALIDATE_EMAIL))
                $errors['admin_email'] = 'Please enter a valid email address';
            if(!filter_var($_REQUEST['system_email'], FILTER_VALIDATE_EMAIL))
                $errors['system_email'] = 'Please enter a valid email address';
            
            if (count($errors)) {
                echo json_encode(array('errors' => $errors));
                exit();
            }            
            
            $settingsData = $settingsModel->getAllSettings(); 
            $cArr = array_flip($settingsData); 
            
            foreach($_POST as $key => $value) {
                
                if(in_array($key, $cArr)) {
                    //update data in the settings
                    $settingsModel->updateData(array('value'=>$value), array('key'=>$key));
                }
                else if($key != 'set'){
                    //insert data to settings
                    $inData = array(
                                'key'=>$key,
                                'value'=>$value
                        ); 
                   $settingsModel->insertData($inData);
                }
            }
        } 
        else {
            
            $response = $settingsModel->getAllSettings();
        }
        
        echo json_encode($response);
        exit();
    }
    
    public function getsitestatAction() {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        $countries = app::inst()->db->query("SELECT * FROM country")->fetchAll();
        $projects = app::inst()->db->query("SELECT * FROM projects ORDER by id")->fetchAll();
        $data = array();
        foreach ($projects as $project) {
            $sites = app::inst()->db->query("SELECT id,country,domain FROM site WHERE project=".$project['id']." ORDER BY domain")->fetchAll();
            foreach ($sites as $site) {
                $slash = strpos($site['domain'], '/');
                if ($slash===false) $domain = $site['domain'];
                else $domain = substr($site['domain'], 0, $slash);
                $data[$project['name']][$domain][$site['country']] = array(
                    'ad' => app::inst()->db->query("SELECT COUNT(*) FROM urls JOIN advertiser_roles ar ON urls.id=ar.urls_id AND ar.role_id=1 WHERE site_id=".$site['id'])->fetch(PDO::FETCH_COLUMN),
                    'pl' => app::inst()->db->query("SELECT COUNT(*) FROM urls JOIN advertiser_roles ar ON urls.id=ar.urls_id AND ar.role_id=3 WHERE site_id=".$site['id'])->fetch(PDO::FETCH_COLUMN),
                    'shown' => app::inst()->db->query("SELECT COUNT(*) FROM urls JOIN advertiser_roles ar ON urls.id=ar.urls_id AND (ar.role_id=1 OR ar.role_id=3) AND urls.published=1 WHERE site_id=".$site['id'])->fetch(PDO::FETCH_COLUMN),
                    'hidden' => app::inst()->db->query("SELECT COUNT(*) FROM urls JOIN advertiser_roles ar ON urls.id=ar.urls_id AND (ar.role_id=1 OR ar.role_id=3) AND urls.published=0 WHERE site_id=".$site['id'])->fetch(PDO::FETCH_COLUMN)
                );
            }
        }
        echo json_encode(array('countries'=>$countries,'data'=>$data));
        exit;
    }
    
    public function setcheckadvsAction() {
        if (isset($_POST['site_id']) && isset($_POST['action'])) {
            $site_id = $_POST['site_id'];
            $action = $_POST['action'];

            $site = app::inst()->db->query("SELECT count(*) FROM advertiser_checking WHERE site_id=".$site_id)->fetchColumn();
            if ($action==1) {
                if ($site > 0) $sql = "UPDATE advertiser_checking SET status=1 WHERE site_id=:site_id";
                else $sql = "INSERT INTO advertiser_checking SET site_id=:site_id, check_date='', status=1";
            } else {
                $sql = "UPDATE advertiser_checking SET status=0 WHERE site_id=:site_id";
            }
            $data = array(
                ':site_id' => $site_id,
            );
            $smtm = app::inst()->db->prepare($sql);
            $result = $smtm->execute($data);
            
            
            $sql = "UPDATE site SET check_advs=:check_advs WHERE id=:id";
            $upData = array(
                ':id' => $site_id,
                ':check_advs' => $action
            );
            $smtm = app::inst()->db->prepare($sql);
            $result = $smtm->execute($upData);
            
            if ($result) {
                $settingsModel = new Settings();
                $from = $settingsModel->getSettingByKey('system_email');
                $to = $settingsModel->getSettingByKey('admin_email');
                $subject = "Wp Redirect message";
                $body = "Advertisers checking status has been changed to ".$action." for site ".$site_id;
                $headers = 'From: '.$from."\r\n";
                //$res = Mailer::sendHtmlMail($from, $to, $subject, $body);
                //mail($to, $subject, $body, $headers);
            }
            
            echo json_encode($result);
            exit;
        }
    }
    
}
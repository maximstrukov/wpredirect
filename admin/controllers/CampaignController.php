<?php

class CampaignController extends BaseController {

    private $logoUploaded = false; 
    private $IMAGE_SCALE_WIDTH = 450;

    function indexAction() 
    {
        Log::init('CampaignController');
        
        $this->registerScriptFile('js/app.campaigns.js');
        
        //Jcorp plugin 
        $this->registerScriptFile(BASE_URL.'js/fileuploader.js');
        $this->registerStyleSheetFile(BASE_URL.'css/jquery.Jcrop.min.css');
        $this->registerScriptFile(BASE_URL.'js/jquery.color.js');
        $this->registerScriptFile(BASE_URL.'js/jquery.Jcrop.min.js');
        $this->registerScriptFile(BASE_URL.'js/partials.editimagetool.js');
        
        // treeview plugin 
        $this->registerStyleSheetFile(BASE_URL.'css/jquery.treeview.css');
        $this->registerScriptFile(BASE_URL.'js/jquery.cookie.js');
        $this->registerScriptFile(BASE_URL.'js/jquery.treeview.js');        
        $this->registerScriptFile(BASE_URL.'js/partials.editcategory.js');
        
        // add tynimce
        $this->registerScriptFile(BASE_URL.'js/tiny_mce/tiny_mce.js');
        $this->registerScriptFile(BASE_URL.'js/tinyMCE.init.js');
        
        $time_options = $this->getTimeOptions();
        $countryCodes = $this->getCountryCodes();
        $openDialogId = 0;
        if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && $_REQUEST['id']>0){
            $openDialogId = (int)$_REQUEST['id'];
        }
        
        $projects = app::inst()->db->query("SELECT * FROM projects")->fetchAll();
        if (isset($_COOKIE['project'])) {
            $active_project = $_COOKIE['project'];
            setcookie('project', '', time() - 3600); 
        } else $active_project = 0;
        
        $siteModel = new Site();
        if ($active_project==0) $sitesData = $siteModel->getSites();
        else $sitesData = $siteModel->getSites(null,"*","project=".$active_project);
        
        $catModel = new Categories();
        if ($_SERVER["SERVER_NAME"]=="wpredirect.loc") $site_id = 7;
        else $site_id = $sitesData[0]['id'];
        $catData = $catModel->getCategoriesWithSites($site_id);
        
        $blacklistModel = new Blacklists(); 
        $blacklistData = $blacklistModel->getDataByFields(array('type'=>'private'), true);       
        
        $templatesModel = new Templates(); 
        $templatesData = $templatesModel->getDataByFields(array(), true);
        
        $this->render('index', array(
            'openDialogId'=>$openDialogId,
            'countryCodes'=>$countryCodes,
            'time_options'=>$time_options,
            'sitesData'=>$sitesData,
            'blacklistData'=>$blacklistData,
            'templatesData'=>$templatesData,
            'catData'=>$catData,
            'projects'=>$projects,
            'active_project'=>$active_project
        ));
    }

    public function prefileuploudAction()
    {
        $img_width = $_REQUEST['width'];
        $img_height = $_REQUEST['height'];
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
        $url = isset($_REQUEST['url']) ? $_REQUEST['url'] : false; 
        
        if ($site_id) {
            
            $siteModel = new Site();
            $response = $siteModel->getSites($site_id); 

            // get width and height logo img 
            $logoData = unserialize($response['logo_data']); 
            
            $img_width = (isset($logoData['logo_width'])) ? $logoData['logo_width'] : '';
            $img_height = (isset($logoData['logo_height'])) ? $logoData['logo_height'] : '';
        }
        
        $aJSON = array('success'=>1, 'raw'=>'', 'type'=>'data:image/jpeg;base64,', 'errors'=>'');
        if ($img_width && $img_height) {
            if (isset($_GET['qqfile'])) {
                # get image from raw request
                $input = fopen("php://input", "rb");
                $data = '';
                while (!feof($input)) {
                    $data .= fread($input, 1024);
                }
                fclose($input);

            } else if ($url) {
                # get image from url
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_HEADER => 0,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_TIMEOUT => 4
                ) );
                
                if(!$data = curl_exec($ch)) {
                    $aJSON['success'] = 0;
                    $aJSON['errors'] = curl_error($ch);
                }
                curl_close($ch);
            }
            
            if ($data){
                $source = imagecreatefromstring($data);
                
                $dbSizesScale = $this->IMAGE_SCALE_WIDTH/$img_width;
                $dbWidth = $img_width*$dbSizesScale;
                $dbHeight = $img_height*$dbSizesScale;
            
                # define image sizes
                $oldWidth = imagesx($source);
                $oldHeight = imagesy($source);
                $scale = $dbWidth/$oldWidth;
                if ($oldWidth < $oldHeight)
                    $scale = $dbHeight/$oldHeight;
                
                $newImageWidth = ceil($oldWidth * $scale);
                $newImageHeight = ceil($oldHeight * $scale);
                
                $distX = $oldWidth > $oldHeight && $oldWidth != $oldHeight && $dbWidth < $oldWidth ? 0 : ($dbWidth - $newImageWidth)/2;
                $distY = $oldWidth > $oldHeight || $oldWidth == $oldHeight || $dbHeight > $oldHeight ? ($dbHeight - $newImageHeight)/2 : 0;

                //print $newImageWidth.'|'.$newImageHeight.'|'.$oldWidth.'|'.$oldHeight.'|'.$distX.'|'.$distY;
                
                # create white layout
                $newImage = imagecreatetruecolor($dbWidth, $dbHeight);
                $white_colour = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white_colour);

                # copy to layout and center it
                imagecopyresampled($newImage,$source,$distX,$distY,0,0,$newImageWidth,$newImageHeight,$oldWidth,$oldHeight);

                ob_start();
                imagejpeg($newImage, NULL, 100);
                $aJSON['raw'] = base64_encode(ob_get_contents());
                ob_get_clean();
                imagedestroy($source);
            }
        } 
        else {
            
            $aJSON['success'] = 0;
            $aJSON['errors'] = 'Please set width and height';
        }
        
        echo json_encode($aJSON); 
        exit();
    }
    
    function tableAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
        $published = isset($_REQUEST['published']) ? $_REQUEST['published'] : false;
        $categoryData = isset($_REQUEST['categoryData']) ? $_REQUEST['categoryData'] : false;
        
        $urlsModel = new Urls();
        $result = $urlsModel->getTableData($site_id, $published, $categoryData);
        
        $advertiserCategoryModel = new AdvertiserCategory(); 
        
        $resArr = array();
        
        foreach($result as $row){
            
            if(empty($row['name'])) continue;
            
            $rRow[0] = (!empty($row['domain'])) ? 'http://'.$row['domain'] : '';
            $rRow[1] = $row['name'];
            $rRow[2] = ($row['country']==-1) ? '' : $row['country'];

            if (strlen($row['ips']) > 255)
                $ips = substr($row['ips'], 0, 255) . ' ...';
            else
                $ips = $row['ips'];
            // hide exceptions ip column
            //$rRow[3] = $ips; 

//            $start = date('H:i', strtotime($row['start']));
//            $end = date('H:i', strtotime($row['end']));
            
            // hide Exception Time column
            //$rRow[4] = $start !== $end ? $start . '-' . $end : '';
            $urlsModel = new Urls();
            
            if(empty($row['categories_tree'])) {
                $categories_tree = $advertiserCategoryModel->getCategoriesByAdvID($row['id']);
                $upTreeData = array('categories_tree' => $categories_tree, 'id' => $row['id']);
                $urlsModel->updateData($upTreeData);
                $row['categories_tree'] = $categories_tree;
            }
                            
            $rRow[3] = $row['categories_tree'];
            $rRow[4] = (!empty($row['param_url'])) ? $row['param_url'] : '';
            
            // get role 
            // get Roles and current role
            $advRolesModel = new AdvertiserRoles();
            $rData = $advRolesModel->getDataByFields(array('urls_id'=>$row['id']), false, array(), array('role_id'));
            $currentRole = $rData['role_id'];
            $rolesModel = new Roles();
            $allRoles = $rolesModel->getDataByFields(array(), true);
            $option = ''; 
            foreach($allRoles as $rItem) {
                $selected = ($rItem['id'] == $currentRole) ? 'selected="selected"' : ''; 
                $option .= '<option value="'.$rItem['type'].'" ' . $selected . ' >'.$rItem['type'].'</option>';
            }
            
            $rRow[5] = '<select onchange="/*setRole(this.value,'.$row['id'].');*/ return false;" id="_role" >'.$option.'</select>';
            
            $rRow[6] = '<a href="#" site_id="'.$row['site_id'].'" wp_post_id="'.$row['wp_post_id'].'" url_id="'.$row['id'].'" onclick="/*set_published(this,\'hide\');*/ return false;" '.(($row['published']==0) ? 'class="set_published"':'').'>hide</a><br />'.
                       '<a href="#" site_id ="'.$row['site_id'].'" wp_post_id="'.$row['wp_post_id'].'" url_id="'.$row['id'].'" /*onclick="set_published(this,\'show\');*/ return false;" '.(($row['published']) ? 'class="set_published"':'').'>show</a>';
            $rRow[7] = '<a href="#" url_id="' . $row['id'] . '" class="edit" onclick="/*open_edit(this);*/ return false;">edit</a> <a href="#" url_id="' . $row['id'] . '" class="delete" onclick="/*open_delete(this);*/ return false;">delete</a>';
            
            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }
    
    public function setpublishedAction()
    {
        $localWpPostID = $_REQUEST['wp_post_id'];
        $status = $_REQUEST['status'];
        $site_id = $_REQUEST['site_id'];
        $id = $_REQUEST['id']; 
        $result = 'failed'; 
        if($localWpPostID && 
                $status && 
                    $site_id && 
                        $id) {
            
            $state = ($status == 'hide') ? false : true;
            // set state in the db 
            
            $urlsModel = new Urls(); 
            $urlsModel->updateData(array(
                'published' => $state,
                'id' => $_POST['id']
            ));
            
            // set state in the minisite
            $objXMLRP = WpAdmin::getXMLRPobj($site_id);
            $response = $objXMLRP->set_published($localWpPostID, $state);
            $rObj = simplexml_load_string($response);   
            $result = 'set'; 
        }
        
        echo json_encode($result); 
        exit();
    }
    
    private function uploadCropLogo($site_id, $advertiser_id) 
    {        
        $result = false;
        
        $scale = $_POST['_scale'] ? $_POST['_scale'] : 1;
        $width = $_POST['_w']*$scale;
        $height= $_POST['_h']*$scale;
        $start_width = $_POST['_x1']*$scale;
        $start_height = $_POST['_y1']*$scale;
        
        $source = @imagecreatefromstring(base64_decode($_POST['_img_raw']));
        
        if ($source) {
            
            $LogoWH = WpAdmin::getLogoWH($site_id);
            
            $newImageWidth = $LogoWH['logo_width'];
            $newImageHeight = $LogoWH['logo_height'];

            # create white layout
            $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
            $white_colour = imagecolorallocate($newImage, 255, 255, 255);
            imagefill($newImage, 0, 0, $white_colour);

            # copy to layout and center it
            imagecopyresampled($newImage, $source, 0, 0, $start_width, $start_height, $newImageWidth, $newImageHeight, $width, $height);

            ob_start();
            imagejpeg($newImage, NULL, 100);
            $img_raw = base64_encode(ob_get_contents()); //!!! that image
            ob_get_clean();
            imagedestroy($source);            
            
            // Save file to local server 
            $filename = $advertiser_id.'.jpg';
            $savefolder = dirname(__FILE__).'/../../upload/'.$site_id; 
            
            // make upload dir 
            @mkdir($savefolder, 0777, true);      
            
            $file = $savefolder.'/'.$filename;
            
            // Open the file to get existing content
            $file_content = base64_decode($img_raw);

            // Write the contents back into the file
            if(file_put_contents($file, $file_content)) {
                
                $this->logoUploaded = true;
                $result = true;             
            }
        }
        
        return $result;         
    }
    
    public function saveAction() 
    {        
        Log::init('CampaignController');
        Log::start('saveAction');
        
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        if ($_POST) {
            
            $errors = $this->validate($_POST);

            if (count($errors)) {
                
                Log::end();
                echo json_encode(array('errors' => $errors));
                exit();
            }

            if (intval($_POST['id'])) {
                
                // set featured_post
                $featured_post = isset($_POST['featured_post']) ? true : false;
                $wp_page_order = ($featured_post) ? 0 : 2147483647;
                $cir_featured = ($featured_post) ? 'yes' : 'no';
                
                // upload logo image file
                $advertiser_id = intval($_POST['id']); 
                $site_id = intval($_POST['sites']);     
                
                // upload crop logo 
                $this->uploadCropLogo($site_id, $advertiser_id);
                
                // check moving or not Post(Adv)
                $movingPost = $this->movingPost($_POST['name'], $_POST['id'], $site_id);
                
                // send image by rpc api to wp minisite
                $mediaId = null;
                $logoWPurl = '';
                if($this->logoUploaded) {
                    
                    $logoData = WpAdmin::sendLogoToWpByRpc($site_id, $advertiser_id);
                    
                    if (empty($logoData['url'])) {
                        
                        Log::l('Can\'t upload file to the remote server. Response (of sendLogoToWpByRpc): '.json_encode($logoData), Zend_Log::ERR);
                        Log::end();
                        echo json_encode(array('errors' => array('myfile_errors'=>"Can't upload file to the remote server. Possible permissions problem.")));
                        exit();
                    }
                    
                    $logoWPurl = $logoData['url'];
                    $mediaId = $logoData['id'];
                    $this->logoUploaded = false;
                }
                // end upload file
                
                // prepare description logo                
                $description = $_POST['description'];
                
//                $len = strpos($description, '. ')+1;
//                
//                if($len<50)
//                    $excerptDesc = substr($description, 0, strpos($description, '. ', $len)); // cut two sentence
//                else $excerptDesc = substr($description, 0, $len); // cut only one sentence
//                
//                if(!empty($excerptDesc)) $excerptDesc .= '.';
                
                $excerptDesc = WpAdmin::getFirstSentence($description);
                //Log::l('Cutting excerpt is: '.$excerptDesc); 
                // cutting description
                //$excerptDesc = (strlen($description)>100) ? substr($description, 0, 100).'...' : $description;
                
                $this->populateProviders($_POST['id']);

                // sql update was here --->
                // prepare data for "Post" to WP by XML RPC api 
                $site_id = intval($_POST['sites']);
                $objXMLRP = WpAdmin::getXMLRPobj($site_id);
                
                // managing adv categories 
                $site_categories = $_POST['site_category'];

                foreach ($site_categories as $n => $sc) {
                    $cats = str_replace('&', '&amp;', $sc);
                    $site_categories[$n] = str_replace('&amp;amp;','&amp;',$cats);
                }
                
                $site_categories_serialize = serialize($site_categories);
                //echo $site_categories_serialize;
                //var_dump($site_categories);
                //exit;
                $advertiserCategoryModel = new AdvertiserCategory(); 
                $advertiserCategoryModel->assignAdvToCategories($_POST['id'], $_POST['site_category'], $site_id);
                
                // send data
                $title = trim($_POST['name']);
                $put_desc_logo = isset($_POST['desc_logo']) ? $_POST['desc_logo'] : false; 
                $body = WpAdmin::concatLogoToDesc($logoWPurl, $description, $advertiser_id, $site_id, $put_desc_logo);
                $category = $site_categories;
                $keywords = $title; 
                $wp_post_thumbnail = $mediaId;
                
                // set desc_logo_url 
                $urlsModel = new Urls();
                $uData = $urlsModel->getUrlsData($advertiser_id); 
                $db_desc_logo = $uData['desc_logo_url'];
                $descriptionLogo = !empty($logoWPurl) ? $logoWPurl : $db_desc_logo;
//                $search = $advertiser_id.'.jpg'; 
//                $replace = $advertiser_id.'-150x150.jpg';
//                $descriptionLogo = false; 
//                if(!empty($logoWPurl))
//                    $descriptionLogo = str_replace($search, $replace, $logoWPurl);

                $localWpPostID = false; 
                // check existing advertiser in local db and get that wp_post_id 
                $localWpPostID = $this->checkExistAdv($_POST['name'], $_POST['id'], $site_id);
                
                Log::l("Try to get local_wp_post_id via name={$_POST['name']}, urls_id={$_POST['id']} where site_id={$site_id}. Result is:[".$localWpPostID.']');
                
                $redirectUrl = isset($_POST['redirect_url']) ? urldecode($_POST['redirect_url']) : '';
                $exceptionUrl = urldecode($_POST['exception_url']);
                $exceptionUrl2 = urldecode($_POST['exception_url2']);
                $emailParam = $_POST['email_param'];
                
                $showiframe = isset($_POST['show_iframe']) ? 1 : 0;
                
                // get role type: 
                $role_id = isset($_REQUEST['role']) ? $_REQUEST['role'] : false;
                $role = 'advertiser';
                $rolesModel = new Roles();
                
                if($role_id) {    
                    $rData = $rolesModel->getDataByFields(array('id'=>$role_id));
                    $role = $rData['type'];
                }
                else {
                    $rData = $rolesModel->getDataByFields(array('type'=>$role));
                    $role_id = $rData['id'];
                }
                
                $customfields = array(
                    array('key'=>'email_param', 'value'=>$emailParam),
                    array('key'=>'link', 'value'=>$redirectUrl),
                    array('key'=>'exception_url', 'value'=>$exceptionUrl),
                    array('key'=>'exception_url2', 'value'=>$exceptionUrl2),
                    array('key'=>'image', 'value'=>$descriptionLogo),
                    array('key'=>'mt_excerpt', 'value'=>$excerptDesc),
                    array('key'=>'role', 'value'=>$role),
                    array('key'=>'featured', 'value'=>$cir_featured),
                    array('key'=>'showiframe', 'value'=>$showiframe)
                );                 
                
                if(intval($localWpPostID)&&
                    !is_null($localWpPostID)&&
                        !empty($localWpPostID)) {
                    
                    $customfields_edit = WpAdmin::getEditCustomFields($site_id, $localWpPostID, $emailParam, $redirectUrl, $exceptionUrl, $exceptionUrl2, $descriptionLogo, $role, $cir_featured, $showiframe);

                    if($movingPost) {
                        
                        Log::l('Try moving post..');
                        Log::l('Passed pramas to RPC create_post (custom_fields): '.json_encode($customfields));
                        $response = $objXMLRP->create_post($title, $body, $category, $keywords, $customfields, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
                        $rObj = simplexml_load_string($response);      
                        Log::l('Result of RPC create_post: '.json_encode($rObj));
                        $wp_post_id = isset($rObj->params->param->value->string) ? 
                                                $rObj->params->param->value->string :
                                                    false;

                        if(!intval($wp_post_id)) {
                            $errorRes = @$rObj->fault->value->struct->member[1]->value->string;
                            $errors['post_errors'] = '(moving post)Error:'.$errorRes.' The "metaWeblog.newPost" request was not sent please try again.';
                            Log::end(); 
                            echo json_encode(array('errors' => $errors));
                            exit();
                        }                        
                    } 
                    else {
                        
                        Log::l('Passed pramas to RPC edit_post : custom_fields:{'.json_encode($customfields_edit).'}, '.
                                "localWpPostID = $localWpPostID, title = $title, body = $body, category = $category, keywords = $keywords, 'UTF-8', wp_post_thumbnail = $wp_post_thumbnail, excerptDesc = $excerptDesc");
                        $response = $objXMLRP->edit_post($localWpPostID, $title, $body, $category, $keywords, $customfields_edit, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
                        $rObj = simplexml_load_string($response);      
                        Log::l('Result of RPC edit_post: '.json_encode($rObj));
                        $boolRes = isset($rObj->params->param->value->boolean) ? 
                                            $rObj->params->param->value->boolean :
                                                false;

                        $wp_post_id = $localWpPostID;

                        if(!$boolRes) {
                            $errorRes = @$rObj->fault->value->struct->member[1]->value->string;
                            $errors['post_errors'] = 'Error:'.$errorRes.' The "metaWeblog.editPost" request was not sent please try again.';
                            Log::end();
                            echo json_encode(array('errors' => $errors));
                            exit();
                        }
                    }
                }
                else {
                    // test
//                    $cfData = WpAdmin::getPostCustomFields($site_id, 2706);
//                    Log::l('Result of getting custom fields of current post: '.  json_encode($cfData));
//                        Log::end(); 
//                        $errors['post_errors'] = 'Error: Can not save advertiser, please try again later.'; 
//                        echo json_encode(array('errors' => $errors));
//                        exit();                        
                    // test 

                    // if not isset value of wp_post_id excute "create_post"
                    Log::l('Passed pramas to RPC create_post (custom_fields): '.json_encode($customfields));
                    $response = $objXMLRP->create_post($title, $body, $category, $keywords, $customfields, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
                    $rObj = simplexml_load_string($response);      
                    Log::l('Result of RPC create_post: '.json_encode($rObj));
                    $wp_post_id = isset($rObj->params->param->value->string) ? 
                                            $rObj->params->param->value->string :
                                                false;

                    if(!intval($wp_post_id)) {
                        $errorRes = @$rObj->fault->value->struct->member[1]->value->string;
                        $errors['post_errors'] = 'Error:'.$errorRes.' The "metaWeblog.newPost" request was not sent please try again.';
                        Log::end(); 
                        echo json_encode(array('errors' => $errors));
                        exit();
                    } 
                    
                    // Check custom fields, compaire with params which user entry 
                    $wp_post_id = (int) $wp_post_id;
                    Log::l('Start  WpAdmin::getPostCustomFields with param $site_id ='.$site_id.' and $wp_post_id ='.$wp_post_id);
                    $cfData = WpAdmin::getPostCustomFields($site_id, $wp_post_id);
                    
                    if($cfData['exception_url2']['value'] != $exceptionUrl2) {
                        
                        // delete post by rpc-api
                        $objXMLRP->delete_post($wp_post_id);
                        Log::l('Result of getting custom fields of current post: '.  json_encode($cfData));
                        Log::l('Check custom fields (exception_url2 (TU2)): user value['.$exceptionUrl2.'] != get value ['.$cfData['exception_url2']['value'].']', Zend_log::ERR);
                        Log::end(); 
                        $errors['post_errors'] = 'Error: Can not save advertiser, please try again later.'; 
                        echo json_encode(array('errors' => $errors));
                        exit();                        
                    }
                }
                
                $desc_logo_state = isset($_POST['desc_logo']) ? 1 : 0;
                
                // updating parametrize url 
                $wp_post_id = (int) $wp_post_id;
                WpAdmin::updateParamUrl($site_id, $_POST['id'], $wp_post_id);
                
                // assign blacklist to advertiser 
                WpAdmin::saveAdvertiserBlacklist($_POST['blacklist_id'], $_POST['id']);
                
                //assign template to advertiser 
                $template_id = isset($_POST['template_id'])&&!empty($_POST['template_id']) ? $_POST['template_id'] : false;
                $advTempModel = new AdvertiserTemplate(); 
                if($template_id) {
                    
                    $isData = array(); 
                    $isData = $advTempModel->getDataByFields(array('urls_id'=>$_POST['id']));
                    
                    if(!empty($isData)) {
                        $advTempModel->updateData(array('template_id'=>$template_id),array('urls_id'=>$_POST['id']));
                    }
                    else $advTempModel->insertData(array('urls_id'=>$_POST['id'], 'template_id'=>$template_id));
                } 
                else $advTempModel->deleteData(array('urls_id'=>$_POST['id']));
                
                $sites = new Site();
                $less_strict = (isset($post['sites'])) ? $sites->getLessStrictVal((int)$post['sites']) : false;
                $exception_country = ($less_strict) ? '' : @$_POST['exception_country'];
                
                $urlsModel = new Urls(); 
                $upData = array(
                        'name' => $_POST['name'],
                        'redirect_url' => $redirectUrl,
                        'exception_url' => $_POST['exception_url'],
                        'exception_url2' => $_POST['exception_url2'],
                        'ips' => @$_POST['ips'],
                        'country' => $exception_country,
                        'start' => $_POST['start'],
                        'end' => $_POST['end'],
                        'description' => $description,
                        'site_category' => $site_categories_serialize ,
                        'site_id' => $_POST['sites'],
                        'wp_post_id' => $wp_post_id,
                        'desc_logo_url' => $descriptionLogo, 
                        'desc_logo' => $desc_logo_state, 
                        'featured_post' => (int)$featured_post,
                        'show_iframe' => (int)$showiframe,
                        'status' => "saved",
                        'id' => $_POST['id']
                );
                $urlsModel->updateData($upData);
                
                Log::l('Save advertiser in local db with next data: '.json_encode($upData));
                
                // set categories_tree field for current advertiser
                $categories_tree = $advertiserCategoryModel->getCategoriesByAdvID($advertiser_id); //categories_tree   
                $upTreeData = array('categories_tree' => $categories_tree, 'id' => $advertiser_id);
                $urlsModel->updateData($upTreeData);
                
                $_SESSION['previous_urls_id'] = $_POST['id']; 
                setcookie('previous_urls_id', $_POST['id']);
                
                // set role for post
                $advRolesModel = new AdvertiserRoles(); 
                $whereFields = array('urls_id' => $_POST['id']);
                $checkExist = $advRolesModel->getDataByFields($whereFields);
               
                $setRoleData = ($role_id) ? array('urls_id'=>$_POST['id'], 'role_id'=>$role_id) : array() ;

                if(!empty($checkExist) && 
                        !empty($setRoleData))
                    $advRolesModel->updateData($setRoleData, $whereFields);
                else if(!empty($setRoleData)) $advRolesModel->insertData($setRoleData);
            }
        }

        Log::end(); 
        echo json_encode("saved");
        exit();
    }
    
    public function assignAction() 
    {
        $advertiserCategoryModel = new AdvertiserCategory(); 
        
        $urlsModel = new Urls(); 
        $uData = $urlsModel->getDataByFields(array(), true);
        
        foreach($uData as $val) {
            
            $urls_id = $val['id']; 
            $site_category = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $val['site_category']);
            $categories_name_arr = @unserialize($site_category);
            $site_id = $val['site_id']; 
            
            $advertiserCategoryModel->assignAdvToCategories($urls_id, $categories_name_arr, $site_id);
        }
        
        die('done');
    }
    
    private function movingPost($adv_name, $adv_id, $site_id)
    {
        $moving = false; 
        
        $urlsModel = new Urls();
        $uData = $urlsModel->getUrlsData($adv_id, $adv_name);
        
        $getSiteID = $uData['site_id'];
        $post_id = $uData['wp_post_id'];
        
        if(!empty($getSiteID) &&
                !empty($post_id)) {
            
            if($getSiteID != $site_id) {    
                // delete post from current minisite 
                $objXMLRP = WpAdmin::getXMLRPobj($getSiteID);
                $objXMLRP->delete_post($post_id); 
                $moving = true;

                // move logo image form old site to new site
                $oldFile = dirname(__FILE__).'/../../upload/'.$getSiteID.'/'.$adv_id.'.jpg';
                if(file_exists($oldFile)) {

                    // create new directory for new logo file 
                    $newfolder = dirname(__FILE__).'/../../upload/'.$site_id;
                    @mkdir($newfolder, 0777, true);
                    // move old file to new logo directory 
                    if(copy($oldFile, $newfolder.'/'.$adv_id.'.jpg')) {
                        Log::l('New image: '.$newfolder.'/'.$adv_id.'.jpg');
                        // remove old file 
                        @unlink($oldFile); 
                        // send image logo by rpc api 
                        $this->logoUploaded = true; 
                    }
                }
            }
        }
        
        return $moving;
    }
    
    private function checkExistAdv($realName, $urls_id, $site_id) 
    {
        $localWpPostID = false; 
        $urlsModel = new Urls();
        
        // check existing of 'wp_post_id' value and get redirect_url from local db        
        $result = $urlsModel->getUrlsData($urls_id);

        $localWpPostID = (isset($result['wp_post_id']) && !empty($result['wp_post_id'])) ? $result['wp_post_id'] : false;
        
        // search by name
        if(!$localWpPostID) {
            
            // search by name            
            $urlsModel = new Urls();
            $result = $urlsModel->getDataByFields(array('site_id'=>$site_id), true);

            $rname = str_replace(' ','',$realName);
            $searchName = strtolower($rname);

            foreach($result as $urlsRow) {

                $r_name = str_replace(' ','',$urlsRow['name']);
                $dbSearchName = strtolower($r_name);

                if($searchName == $dbSearchName) {
                    
                    $localWpPostID = $urlsRow['wp_post_id'];

                    // remove old post 
                    $urlsModel->deleteByID($urlsRow['id']); 
                    
                    Log::l('Row was deleted [with name="'.$searchName.'" and site_id='.$site_id.'] via query: DELETE from `urls` WHERE  id = '.$urlsRow['id'].' Deleted row is: '.  json_encode($urlsRow), Zend_Log::ERR);
                    
                    $sql = 'update `urls_logs` set url_id = :set_url_id WHERE  url_id = :url_id';
                    $smtm = app::inst()->db->prepare($sql);
                    $smtm->execute(array(':set_url_id' => $urls_id, 
                                            ':url_id' => $urlsRow['id'])); 
                    break;
                }
            }            
        }

        return $localWpPostID;
    }
    
    private function validate($post)
    {        
        $sites = new Site();
        $less_strict = (isset($post['sites'])) ? $sites->getLessStrictVal((int)$post['sites']) : false;        

        $errors = array();
        
        if(empty($post['sites'])) 
            $errors['sites'] = 'Please select a site';
        
        if(empty($post['site_category']))
            $errors['site_category'] = 'Please select the category';
        
        if ($post['name'] == '')
            $errors['name'] = 'Is not valid';
        
        if (isset($post['redirect_url']) && !$this->isValidURL($post['redirect_url']) && !$less_strict)
            $errors['redirect_url'] = 'Is not valid';
        
        if (!$this->isValidURL($post['exception_url']))
            $errors['exception_url'] = 'Is not valid';
        
        if (!$this->isValidURL($post['exception_url2']) && !empty($post['exception_url2']))
            $errors['exception_url2'] = 'Is not valid';

        if (!preg_match('/\d\d:\d\d/i', $post['start']))
            $errors['start'] = 'Wrong format';
        
        if (!preg_match('/\d\d:\d\d/i', $post['end']))
            $errors['end'] = 'Wrong format';

        $start = date('H:i', strtotime($post['start']));
        $end = date('H:i', strtotime($post['end']));

        if ($start > $end)
            $errors['start'] = 'Start couldn\'t be later than end';

        if ($post['exception_country'] == -1 && !$less_strict)
            $errors['exception_country'] = 'Select country';

        if (trim($post['ips'])) {

            $tmp_ips = preg_replace('/\\s+-\\s+/s', ' ', $post['ips']);

            $ips = preg_split("/[\s,;]+/", trim($tmp_ips));
            
            foreach ($ips as $ip) {
                
                $test_ip = str_replace('*', 255, $ip);

                if (ip2long($test_ip) === false) {
                    
                    $errors['ips'] = 'Check IPs';
                    break;
                }
            }
        }

        return $errors;
    }

    function getinfoAction() 
    {
        global $base_dir;
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $rolesModel = new Roles();
        
        if (isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
            
            $urlsModel = new Urls(); 
            $result = $urlsModel->getUrlsData($_REQUEST['id']);
            $response = $result;
            
            $site_id = (int)$response['site_id'];
            
            // get all sites 
            $siteModel = new Site();
            $sites = $siteModel->getSites(); 
            // --- new params 
            $response['sites'] = $sites;
            $site_category = preg_replace('!s:(\d+):"(.*?)";!', "'s:'.strlen('$2').':\"$2\";'", $result['site_category']);
            $response['site_category'] = @unserialize($site_category);
                        
            // get less strict value 
            $sData = $siteModel->getDataByFields(array('id'=>$site_id));
            $response['less_strict'] = $sData['less_strict'];
            
            // get email_param 
            if(!empty($result['site_id']) &&
                    !empty($response['wp_post_id'])) {
                
                $postCFData = WpAdmin::getPostCustomFields($result['site_id'], $response['wp_post_id']); 
//                echo '<pre>';
//                print_r($postCFData); die(); 
                $response['email_param'] = $postCFData['email_param']['value']; 
            }
            
           // check if file exist 
            $response['logo_img'] = false;
            if(!empty($result['site_id'])&&
                !empty($result['id'])) {

                $pathFile = dirname(__FILE__).'/../../upload/'.$result['site_id'].'/'.$result['id'].'.jpg';
                if(file_exists($pathFile))                
                    $response['logo_img'] = 'http://'.$_SERVER['HTTP_HOST'].'/upload/'.$result['site_id'].'/'.$result['id'].'.jpg';    
            }
            
            // get heigth and width of logo image 
            $logoWH = WpAdmin::getLogoWH($site_id);
            $response['logo_height'] = $logoWH['logo_height'];
            $response['logo_width'] = $logoWH['logo_width'];
            
            
            $response['start'] = date('H:i', strtotime($response['start']));
            $response['end'] = date('H:i', strtotime($response['end']));

            $urlProvidersTmpModel = new UrlsProvidersTmp(); 
            
            $urlProvidersTmpModel->deleteByUrlsIDForce($_REQUEST['id']);
            $urlProvidersTmpModel->addDataFromUrlsProviderTableByID($_REQUEST['id']); 
            
            // get blacklist_id if exist 
            $abModel = new AdvertiserBlacklist(); 
            $abData = $abModel->getDataByFields(array('urls_id'=>$_REQUEST['id']));
            $response['blacklist_id'] = !empty($abData['blacklist_id']) ? $abData['blacklist_id'] : '';
            
            // get template_id if exist 
            $advTempModel = new AdvertiserTemplate(); 
            $abData = $advTempModel->getDataByFields(array('urls_id'=>$_REQUEST['id']));
            $response['template_id'] = !empty($abData['template_id']) ? $abData['template_id'] : '';
            
            // get Roles and current role
            $advRolesModel = new AdvertiserRoles();
            $rData = $advRolesModel->getDataByFields(array('urls_id'=>$_REQUEST['id']), false, array(), array('role_id'));
            $currentRole = $rData['role_id'];
            
            $allRoles = $rolesModel->getDataByFields(array(), true); 
            $response['role'] = $currentRole; 
            $response['roles'] = $allRoles; 
        } 
        else {
            
            $urlsModel = new Urls(); 
            $id = $urlsModel->addEmptyField(); 
            
            // get all sites 
            $siteModel = new Site();
            $sites = $siteModel->getSites(); 
          
            // --- new params 
            $response['sites'] = $sites;
            
            // --- old params 
            $response['id'] = $id;
            $response['name'] = '';
            $response['redirect_url'] = '';
            $response['exception_url'] = '';
            $response['country'] = '';
            $response['start'] = '00:00';
            $response['end'] = '00:00';
            $response['enter_url'] = 'http://' . $_SERVER['SERVER_NAME'] . $base_dir . md5(time());
            
            // Save previus positions 
            
            $current_urls_id = $id;
            $pre_urls_id = (isset($_SESSION['previous_urls_id']) && !empty($_SESSION['previous_urls_id'])) ? $_SESSION['previous_urls_id'] :
                                (isset($_COOKIE['previous_urls_id']) && !empty($_COOKIE['previous_urls_id'])) ? $_COOKIE['previous_urls_id'] : 
                                    false;
            if($pre_urls_id) {
                
                $urlsProvidersTmpModel = new UrlsProvidersTmp(); 
                $result = $urlsProvidersTmpModel->getDataByUrlsID($pre_urls_id);
                
                if(empty($result)) {
                    
                    $urlsProvidersTmpModel->addDataFromUrlsProviderTableByID($pre_urls_id);                    
                    $urlsProvidersTmpModel->changeUrlsID($pre_urls_id, $current_urls_id);
                }
            }
            
            // get Roles and current role    
            $allRoles = $rolesModel->getDataByFields(array(), true);            
            $response['roles'] = $allRoles;             
            
            // get less strict value 
            $response['less_strict'] = 0;             
        }
        
        echo json_encode($response);
        exit();
    }
    
    public function getcategoryAction() 
    {
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
        $category = isset($_REQUEST['category']) ? $_REQUEST['category'] : false;
        
        $catModel = new Categories();
        if ($category) {
            $categories = $catModel->getSubCategoriesByCategory($category, $site_id);
            if (isset($_REQUEST["elem_id"])) {
                $res["elem_id"] = $_REQUEST["elem_id"];
                $res["categories"] = $categories;
                $categories = $res;
            }
        } else {
            if(isset($_REQUEST['filter'])) 
                $categories = (!empty($site_id)) ? $catModel->getCategoriesWithSites($site_id) : $catModel->getCategoriesWithSites();
            else $categories = $catModel->getCategoriesBySiteID($site_id);
        }
        echo json_encode($categories);
        exit();
    }
    
    public function getlogodataAction() 
    {
        $response = array(); 
        
        if(!empty($_POST['site_id'])) {
            
            $site_id = $_POST['site_id']; 
            
            $response = WpAdmin::getLogoWH($site_id);
        }        
        
        echo json_encode($response);
        exit();                
    }
    
    public function getlessstrictAction() 
    {
        $response = 0; 
        
        if(!empty($_POST['site_id'])) {
            
            $site_id = (int)$_POST['site_id']; 
            
            $siteModel = new Site(); 
            $sData = $siteModel->getDataByFields(array('id'=>$site_id));
            $response = $sData['less_strict'];
        }        
        
        echo json_encode($response);
        exit();                        
    }
    
    public function getshowiframeAction() 
    {
        $response = 0;
        
        if(!empty($_POST['site_id'])) {
            
            $site_id = (int)$_POST['site_id']; 
            
            $siteModel = new Site();
            $sData = $siteModel->getDataByFields(array('id'=>$site_id));
            $response = $sData['show_iframe'];
        }        
        
        echo json_encode($response);
        exit();                        
    }    
    
    function cancelAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {
            // delete just added ISPs
            $urlsProvidersTmpModel = new UrlsProvidersTmp(); 
            $urlsProvidersTmpModel->deleteByUrlsIDForce($_REQUEST['id']);            
        
            // revert back just removed ISPs
            $urlsModel = new Urls();
            $urlsModel->revertBack($_REQUEST['id']);
        }
        
        echo json_encode('ok');
        exit();
    }

    function deleteAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {
            
            // delete img logo file 
            $urlsModel = new Urls(); 
            $result = $urlsModel->getUrlsData($_REQUEST['id']);
            
            if(!empty($result)) {
                
                $site_id = (count($result)) ? $result['site_id'] : false;
                if($site_id) { 
                    
                    $filename = $result['id'].'.jpg';
                    $path = dirname(__FILE__).'/../../upload/'.$site_id.'/'.$filename; 
                    @unlink ($path);
                    
                    // delete post by rpc-api
                    $objXMLRP = WpAdmin::getXMLRPobj($site_id); 
                    $objXMLRP->delete_post($result['wp_post_id']);
                }
            }
            
            $urlsProvidersTmpModel = new UrlsProvidersTmp(); 
            $urlsProvidersTmpModel->deleteByUrlsIDForce($_REQUEST['id']); 

            $urlsProvidersModel = new UrlsProviders(); 
            $urlsProvidersModel->deleteByUrlsID($_REQUEST['id']);
            
            $urlsModel = new Urls(); 
            $urlsModel->deleteByID($_REQUEST['id']);
            
            $advcatModel = new AdvertiserCategory();
            $advcatModel->deleteByUrlsID($_REQUEST['id']);
        }
        
        echo json_encode('ok');
        exit();
    }

    function getisplistAction() 
    {    
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $response = array();
        if (isset($_REQUEST['country_code'])) {
            
            $urlsiptableModel = new UrlsIptable(); 
            $result = $urlsiptableModel->getDataByFields(array('country_code'=>trim(strtoupper($_REQUEST['country_code']))), 
                                                            true, array(), array('*'), array('isp_name'));            
            foreach($result as $row) $response[] = $row;
        }
        
        echo json_encode($response);
        exit();
    }

    function getcurrentisptableAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        $sLimit = "";
        if (isset($_REQUEST['iDisplayStart']) && $_REQUEST['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT ".$_REQUEST['iDisplayStart'].", ".$_REQUEST['iDisplayLength'];
        }

        $sWhere = "";
        if (isset($_REQUEST['sSearch']) && $_REQUEST['sSearch'] != "") {

            if (preg_match('/^([0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3})\\s*-\\s*([0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3})$/', $_REQUEST['sSearch'], $matches)) {
                $start_ip = sprintf("%u", ip2long(str_replace('*', 0, $matches[1])));
                $end_ip = sprintf("%u", ip2long(str_replace('*', 255, $matches[2])));

                if ($start_ip && $end_ip) {
                    $sWhere = "AND ";
                    $sWhere .= "( (" . $start_ip . " between start_ip and end_ip )";
                    $sWhere .= " OR (" . $end_ip . " between start_ip and end_ip )";
                    $sWhere .= " OR (start_ip between " . $start_ip . " and " . $end_ip . ")";
                    $sWhere .= " OR (end_ip between " . $start_ip . " and " . $end_ip . ")";
                    $sWhere .= ")";
                }
            } elseif (preg_match('/^[0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3}\\.[0-9*]{1,3}$/', $_REQUEST['sSearch'])) {

                $start_ip = sprintf("%u", ip2long(str_replace('*', 0, $_REQUEST['sSearch'])));
                $end_ip = sprintf("%u", ip2long(str_replace('*', 255, $_REQUEST['sSearch'])));

                if ($start_ip && $end_ip) {
                    $sWhere = "AND ";
                    $sWhere .= "( (" . $start_ip . " between start_ip and end_ip )";
                    $sWhere .= " OR (" . $end_ip . " between start_ip and end_ip )";
                    $sWhere .= " OR (start_ip between " . $start_ip . " and " . $end_ip . ")";
                    $sWhere .= " OR (end_ip between " . $start_ip . " and " . $end_ip . ")";
                    $sWhere .= ")";
                }
            } else {
                $sWhere = "AND ";
                $sWhere .= "`isp_name` LIKE '%" . App::escape($_REQUEST['sSearch']) . "%' ";
            }
        }

        $aSelected = array();
        if (isset($_REQUEST['selected']) && $_REQUEST['selected'] != 'true')
            $aSelected[] = " stat=0 ";
        if (isset($_REQUEST['unselected']) && $_REQUEST['unselected'] != 'true')
            $aSelected[] = " stat=1 ";;
        $sSelected = (count($aSelected) ? " HAVING (" . implode(' OR ', $aSelected) . ") " : "");

        /* total */
        $sql = "SELECT SQL_CALC_FOUND_ROWS isp_name, if(urls_id is null, 0, 1) as stat FROM urls_isps as ui
            LEFT JOIN urls_providers_tmp as upt on upt.provider_name = ui.isp_name and 	urls_id = " . intval($_REQUEST['id']) . "
            WHERE country_code = '" . App::escape($_REQUEST['country_code']) . "'"
                . $sSelected
                . $sLimit;

//        $result = mysql_query($sql);
        app::inst()->db->query($sql)->execute();

        $sql = "SELECT FOUND_ROWS()";
//        $result = mysql_query($sql);
        $aResultTotal = app::inst()->db->query($sql)->fetch();
//        $aResultTotal = mysql_fetch_array($result);
        $iTotal = $aResultTotal[0];

        /* select results */

        $sql = "SELECT SQL_CALC_FOUND_ROWS isp_name, if(urls_id is null, 0, 1) as stat, IPnumbers FROM urls_isps as ui
            LEFT JOIN urls_providers_tmp as upt on upt.provider_name = ui.isp_name and 	urls_id = " . intval($_REQUEST['id']) . "
            WHERE country_code = '" . App::escape($_REQUEST['country_code']) . "'"
                . $sWhere
                . $sSelected
                . ' ORDER by IPnumbers desc '
                . $sLimit;
        //die($sql);
        $result = app::inst()->db->query($sql)->fetchAll();

        $sql = "SELECT FOUND_ROWS()";
        $aResultFilterTotal = app::inst()->db->query($sql)->fetch();
        $iFilteredTotal = $aResultFilterTotal[0];

        $res = array();
        foreach($result as $row){
            $status_icon = 'ui-icon-plus';
            if ($row['stat']) {
                $status_icon = 'ui-icon-check';
            }
            
            $res[] = array(
                $row['isp_name'],
                number_format($row['IPnumbers'], 0),
                '<span isp_name="' . htmlentities($row['isp_name']) . '" class="ui-icon ' . $status_icon . '" style="float: right;" onclick="saveprovider(this); return false;"></span>');
        }

        if (!count($res))
            $res[] = array(null, null, null);

        $response = array(
            "sEcho" => intval($_REQUEST['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => $res
        );

        echo json_encode($response);
        exit();
    }
    
    public function selipsbycountAction()
    {
        $country_code = $_REQUEST['country_code'];
        $count = (int)$_REQUEST['count'];
        $urls_id = $_REQUEST['id'];
        
        $urlsProvidersTmpModel = new UrlsProvidersTmp();
        $urlsProvidersTmpModel->deleteByUrlsIDForce($urls_id);
        
        $urlsProvidersTmpModel->selIpsByData($country_code, $urls_id, $count); 
        
        echo json_encode('Done');
        exit();            
    }
    
    public function checkispAction() 
    {
        $result = 'none'; 
        
        $urls_id = $_REQUEST['id'];
        $country_code = $_REQUEST['country_code'];
        
        $urlsProvidersTmpModel = new UrlsProvidersTmp();
        $uptData = $urlsProvidersTmpModel->getDataByFields(array('urls_id'=>$urls_id, 'status'=>'added'), true); //'saved'=>0
        if(count($uptData)>0)
            $result = 'selected'; 
        
        echo json_encode($result);
        exit();                
    }

    public function addispAction() 
    {    
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        if (isset($_REQUEST['id'])) {
            
            $_SESSION['previous_urls_id'] = $_REQUEST['id'];
            setcookie('previous_urls_id', $_REQUEST['id']);
            
            $isp_names = array();
            if (isset($_REQUEST['isp_name'])) {
                $isp_names[] = $_REQUEST['isp_name'];
            }

            if($_REQUEST['status']=='saved') {
                // if status = saved save provider without temp table
                $urlsProvidersModel = new UrlsProviders();
                $urlsProvidersModel->addData($_REQUEST['id'], $isp_names[0], $_REQUEST['status']);
            }
            else {
                
                if (isset($_REQUEST['country_code']) && isset($_REQUEST['search'])) {
                    
                    $sWhere = "";
                    if (isset($_REQUEST['search']) && $_REQUEST['search'] != "") {
                        
                        $sWhere = "AND ";
                        $sWhere .= "`isp_name` LIKE '%" . App::escape($_REQUEST['search']) . "%' ";
                    }

                    /* total */
                    $sql = "SELECT  distinct isp_name FROM urls_iptable
                    WHERE country_code = '" . App::escape($_REQUEST['country_code']) . "'"
                            . $sWhere;

                    $result = app::inst()->db->query($sql)->fetchAll();
                    foreach($result as $row) {    
                        
                        $isp_names[] = $row['isp_name'];
                    }
                }
                else if(isset($_REQUEST['json_isp_names']) && 
                        !empty($_REQUEST['json_isp_names'])) {
                    
                    $isp_names = json_decode($_REQUEST['json_isp_names']);                   
                }

                foreach ($isp_names as $isp_name) { 
                    
                    $urlsProvidersTmpModel = new UrlsProvidersTmp();
                    $urlsProvidersTmpModel->addData($_REQUEST['id'], $isp_name, $_REQUEST['status']);
                }
            }
        }
        
        $response = $_REQUEST['status'];
        echo json_encode($response);
        exit();
    }

    function clearAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        
        if (isset($_REQUEST['id'])) {

            $urls_id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : false;
            $search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : '';
            
            $upModel = new UrlsProviders(); 
            $upModel->updateBySearchData($urls_id, $search); 
            
            $uptModel = new UrlsProvidersTmp(); 
            $uptModel->deleteBySearchData($urls_id, $search);
        }
        
        echo json_encode('ok');
        exit();
    }
    
    private function populateProviders($id)
    {
        $urlsProvidersTmpModel = new UrlsProvidersTmp(); 
        $urlsProvidersTmpModel->deleteByUrlsID($id);
        
        $urlsProvidersModel = new UrlsProviders();
        $urlsProvidersModel->deleteByUrlsID($id); 
        $urlsProvidersModel->addDataFromTmpTableByID($id); 
        $urlsProvidersModel->setSavedByUrlsID($id);
    }
}
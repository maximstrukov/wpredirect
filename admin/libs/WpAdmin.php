<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WpAdmin
 *
 * @author dmitry
 */
class WpAdmin {
    
    public static function getXMLRPobj($site_id)
    {
        // get all posts from remote wp site 
        $siteModel = new Site(); 
        $siteData = $siteModel->getSites($site_id);

        $domain = $siteData['domain'];
        $wp_login = $siteData['wp_login'];
        $wp_pass = $siteData['wp_pass'];

        $rpcUrl = 'http://'.$domain."/xmlrpc.php";
        $objXMLRP = new XMLRPClientWordPress( $rpcUrl, $wp_login , $wp_pass);
        
        return $objXMLRP; 
    }
    
    /**
     * Updating param_url for all advertisers 
     * @param int $site_id (local value id of site)
     * @param int $advertiser_id (optional)
     * @param int $wp_post_id (optional post_id from wp remote site)
     */
    public static function updateParamUrl($site_id, $advertiser_id = null, $wp_post_id = null)
    {
        // get all posts from remote wp site 
        
        $objXMLRP = self::getXMLRPobj($site_id);
        
        // get magic params 
        $magicParam = '';
        $redirectApiObj = new RedirectAPI();
        $magicParam = $redirectApiObj->getMagicPatam($site_id);         
        
        if(is_null($advertiser_id) && 
                is_null($wp_post_id)) {
        
            // update urls 
            $sql = 'SELECT 
                            `urls`.id, 
                            `urls`.param_url,  
                            `urls`.wp_post_id
                    FROM `urls`
                    INNER JOIN site ON site.id = :id AND site.id = `urls`.site_id';
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute(array(':id' => $site_id));
            $res = $smtm->fetchAll();
            
            foreach ($res as $row)  {

                $id = $row['id'];  
                $wp_post_id = $row['wp_post_id'];
                
                self::updateParamUrlItem($id, $wp_post_id, $magicParam, $objXMLRP);
            }
        } 
        else if (!empty($advertiser_id) && 
                    !empty($wp_post_id)) {
            
            self::updateParamUrlItem($advertiser_id, $wp_post_id, $magicParam, $objXMLRP);
        }
    }    
    
    private static function updateParamUrlItem($advertiser_id, $wp_post_id, $magicParam, $objXMLRP)
    {
        $param_url = false;
        //file_put_contents('LOG'.$wp_post_id.'.txt',$wp_post_id.PHP_EOL, FILE_APPEND);
        $getPostData = $objXMLRP->getPost($wp_post_id);
        //$getPostData = $objXMLRP->get_post($wp_post_id);
        $rObj = simplexml_load_string($getPostData);

        // Try again        
        if(isset($rObj->fault)) {
            $getPostData = $objXMLRP->getPost($wp_post_id);
            $rObj = simplexml_load_string($getPostData);
        }        

//        if(isset($rObj->fault)) continue; 
//        else
        if(isset($rObj->params->param->value)) {

            $itemObj = $rObj->params->param->value; 

            $memberObj = (array)$itemObj->struct;
            $feed_item = $memberObj['member'];                   

            foreach($feed_item as $option_item) {

                if($option_item->name == 'link')
                    $param_url = (string)$option_item->value->string;
            }
            
            if($param_url)
                if(!strpos($param_url, '?'))
                    $param_url .= '?'.$magicParam;
                else $param_url .= '&'.$magicParam;

            //update param_url here: 
            $sql = 'UPDATE `urls`
                    SET
                    `param_url` = :param_url
                    WHERE  `id` = :id
                ';

            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute(array(
                    ':param_url' => $param_url,
                    ':id' => $advertiser_id
            ));
        }        
    }
    
    public static function getPostCustomFields($local_site_id, $wp_post_id)
    {
        $objXMLRP = self::getXMLRPobj($local_site_id);
        
        $localWpPostID = $wp_post_id; 
        // get id of link of castom_lild 
        $getPostData = $objXMLRP->get_post($localWpPostID);
        $rObj = simplexml_load_string($getPostData);
//        echo '<pre>'; print_r($rObj); die('HERE');
        $getPostArr = isset($rObj->params->param->value->struct->member) ? 
                        $rObj->params->param->value->struct->member : false;
         
        $cfFieldId = false; // link
        $euFieldId = false; // exception_url
        $eu2FieldId = false; // exception_url2
        $epFieldId = false; // email_param
        $imFieldId = false; // image
        $rtFieldId = false; // role type 
//        $mtExcerptId = false; // mt_excerpt
        // CIR set featured cf
        $feFieldId = false; // featured (CIR's)
        $sifFieldId = false; //show iframe
        
        $linkVal = false;
        $exceptionUrlVal = false;
        $exceptionUrl2Val = false;
        $emailParamVal = false;
        $imageParamVal = false;
        $roleParamVal = false;
//        $mtExcerptVal = false;
        $featuredVal = false;
        $sifParamVal = false;

        if(!empty($getPostArr)) {
            
            foreach ($getPostArr as $objData) 
                if(isset($objData->name))
                    if($objData->name == 'custom_fields' &&
                        isset($objData->value->array->data->value)) {
                            $key = 0; 
                            foreach($objData->value->array->data->value as $cfData) {

                                if(isset($cfData->struct->member)) {
                                        
                                    foreach ($cfData->struct->member as $cf) {

                                        if(isset($cf->name)) {

                                            if($cf->name == 'key') {

                                                $cfName = (string)$cf->value->string;

                                                if($cfName == 'link') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) { 
                                                        
                                                        if(isset($searchCfData->name)) {
                                                            if($searchCfData->name == 'id')
                                                                $cfFieldId = (int)$searchCfData->value->string;
                                                        
                                                            if($searchCfData->name == 'value')
                                                                $linkVal = (string)$searchCfData->value->string;
                                                        }
                                                    }
                                                }

                                                if($cfName == 'exception_url') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                            
                                                            if($searchCfData->name == 'id')
                                                                $euFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $exceptionUrlVal = (string)$searchCfData->value->string;
                                                        }
                                                }                                                            
                                                
                                                if($cfName == 'exception_url2') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                            
                                                            if($searchCfData->name == 'id')
                                                                $eu2FieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $exceptionUrl2Val = (string)$searchCfData->value->string;
                                                        }
                                                }                                                

                                                if($cfName == 'email_param') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                           
                                                            if($searchCfData->name == 'id')
                                                                $epFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $emailParamVal = (string)$searchCfData->value->string;                                                            
                                                        }
                                                }                                                            
                                                
                                                if($cfName == 'image') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                           
                                                            if($searchCfData->name == 'id')
                                                                $imFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $imageParamVal = (string)$searchCfData->value->string;                                                            
                                                        }
                                                }                                                
                                                
                                                if($cfName == 'role') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                           
                                                            if($searchCfData->name == 'id')
                                                                $rtFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $roleParamVal = (string)$searchCfData->value->string;                                                            
                                                        }
                                                }
                                                
//                                                if($cfName == 'mt_excerpt') {
//
//                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
//                                                        if(isset($searchCfData->name)) {
//                                                           
//                                                            if($searchCfData->name == 'id')
//                                                                $mtExcerptId = (int)$searchCfData->value->string;
//                                                            
//                                                            if($searchCfData->name == 'value')
//                                                                $mtExcerptVal = (string)$searchCfData->value->string;                                                         
//                                                        }
//                                                } 
                                                
                                                if($cfName == 'featured') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                           
                                                            if($searchCfData->name == 'id')
                                                                $feFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $featuredVal = (string)$searchCfData->value->string;                                                            
                                                        }
                                                }
                                                
                                                if($cfName == 'showiframe') {

                                                    foreach($objData->value->array->data->value[$key]->struct->member as $searchCfData) 
                                                        if(isset($searchCfData->name)) {
                                                           
                                                            if($searchCfData->name == 'id')
                                                                $sifFieldId = (int)$searchCfData->value->string;
                                                            
                                                            if($searchCfData->name == 'value')
                                                                $sifParamVal = (string)$searchCfData->value->string;                                                            
                                                        }
                                                }                                                
                                                
                                            }                                                     
                                        }                         
                                    }
                                }

                            $key++;
                            }
                        }
        } 
        
        return $result = array(
                    'link' => array('id' => $cfFieldId, 'value'=>$linkVal),
                    'exception_url' => array('id' => $euFieldId, 'value'=>$exceptionUrlVal),
                    'exception_url2' => array('id' => $eu2FieldId, 'value'=>$exceptionUrl2Val),
                    'email_param' => array('id' => $epFieldId, 'value'=>$emailParamVal),
                    'image' => array('id' => $imFieldId, 'value'=>$imageParamVal),
                    'role' => array('id' => $rtFieldId, 'value'=>$roleParamVal),
//                    'mt_excerpt' => array('id' => $mtExcerptId, 'value'=>$mtExcerptVal),
                    'featured' => array('id' => $feFieldId, 'value'=>$featuredVal),
                    'showiframe' => array('id' => $sifFieldId, 'value'=>$sifParamVal),
                );
    }
    
    
    /**
     * getEditCustomFields
     * @param int $site_id
     * @param int $localWpPostID
     * @param string $emailParam
     * @param string $redirectUrl
     * @param string $exceptionUrl
     * @param string $exceptionUrl2
     * @param string $descriptionLogo
     * @param string $role
     * @return array of custom fields 
     */
    public static function getEditCustomFields($site_id, $localWpPostID, $emailParam,  $redirectUrl, $exceptionUrl, $exceptionUrl2, $descriptionLogo, $role, $featured, $showiframe) 
    {
        $cfFieldId = false; // link
        $euFieldId = false; // exception_url
        $eu2FieldId = false; // exception_url2
        $epFieldId = false; // email_param                    
        $imFieldId = false; // image    
        $rtFieldId = false; // role type 
        $feFieldId = false; // featured (for CIR's)
        $sifFieldId = false; // show iframe

        // get id of link of custom_field
        $cfData = self::getPostCustomFields($site_id, $localWpPostID);
        
        $epFieldId = isset($cfData['email_param']['id']) ? $cfData['email_param']['id'] : false; 
        $cfFieldId = isset($cfData['link']['id']) ? $cfData['link']['id'] : false;
        $euFieldId = isset($cfData['exception_url']['id']) ? $cfData['exception_url']['id'] : false;
        $eu2FieldId = isset($cfData['exception_url2']['id']) ? $cfData['exception_url2']['id'] : false;
        $imFieldId = isset($cfData['image']['id']) ? $cfData['image']['id'] : false;
        $rtFieldId = isset($cfData['role']['id']) ? $cfData['role']['id'] : false;
        $feFieldId = isset($cfData['featured']['id']) ? $cfData['featured']['id'] : false;
        $sifFieldId = isset($cfData['showiframe']['id']) ? $cfData['showiframe']['id'] : false;
        
        if(empty($epFieldId)) 
            $customEmailfield = array('key'=>'email_param', 'value'=>$emailParam);
        else $customEmailfield = array('id' => $epFieldId, 'key'=>'email_param', 'value'=>$emailParam);

        if(empty($cfFieldId))
            $customLinkField = array('key'=>'link', 'value'=>$redirectUrl); 
        else $customLinkField = array('id' => $cfFieldId, 'key'=>'link', 'value'=>$redirectUrl);

        if(empty($euFieldId))
            $customExcUrlField = array('key'=>'exception_url', 'value'=>$exceptionUrl);
        else $customExcUrlField = array('id' => $euFieldId, 'key'=>'exception_url', 'value'=>$exceptionUrl);

        if(empty($eu2FieldId))
            $customExcUrl2Field = array('key'=>'exception_url2', 'value'=>$exceptionUrl2);
        else $customExcUrl2Field = array('id' => $eu2FieldId, 'key'=>'exception_url2', 'value'=>$exceptionUrl2);                    

        if(empty($imFieldId))
            $customImField = array('key'=>'image', 'value'=>$descriptionLogo);
        else $customImField = array('id' => $imFieldId, 'key'=>'image', 'value'=>$descriptionLogo); 

        if(empty($rtFieldId))
            $customRtField = array('key'=>'role', 'value'=>$role);
        else $customRtField = array('id' => $rtFieldId, 'key'=>'role', 'value'=>$role);
        
        if(empty($feFieldId))
            $customFeField = array('key'=>'featured', 'value'=>$featured);
        else $customFeField = array('id' => $feFieldId, 'key'=>'featured', 'value'=>$featured);        

        if(empty($sifFieldId))
            $customSifField = array('key'=>'showiframe', 'value'=>$showiframe);
        else $customSifField = array('id' => $sifFieldId, 'key'=>'showiframe', 'value'=>$showiframe);
        
        return array(
            $customEmailfield,
            $customLinkField,
            $customExcUrlField,
            $customExcUrl2Field,
            $customImField, 
            $customRtField,
            $customFeField,
            $customSifField
        );         
    }
    
    
    /**
     * Categories filling data get from RPC response 
     * @param $categories = array of categories
     * @param $site_id = local db id of wp-minisite
     */
    public static function fillCategories($site_id) 
    {
        // get categories from remote site 
        
        $categories = array();
        
        if(!empty($site_id)) {
            
            $objXMLRP = self::getXMLRPobj($site_id);
            $response = $objXMLRP->getCategories();
            $rObj = simplexml_load_string($response);
            
            $categoryArr = isset($rObj->params->param->value->array->data) ? 
                                    $rObj->params->param->value->array->data : 
                                        array();
            
            if(isset($categoryArr->value)) {
                           
                $cnt = 0; 
                foreach($categoryArr->value as $key => $obj) {
                    if(isset($obj->struct->member)) {
                        foreach(@$obj->struct->member as $val) {
                            if(isset($val->name)&&isset($val->value->string))
                                $categories[$cnt][@(string)$val->name] = @(string)$val->value->string;
                        }
                        $cnt++;
                    }
                }
            }
            
            $result = array(); 
            foreach ($categories as $cData) {

                if(strpos($cData['categoryName'], '.com') ||
                        strpos($cData['categoryName'], '.net') ||
                            strpos($cData['categoryName'], '.co.uk') ||
                                strpos($cData['categoryName'], '_')===0 ) continue;

                $result[] = $cData;
            }

            $categories = $result; 


            // fillCategories in the local db
            $catModel = new Categories(); 
            $catModel->deleteCategoriesBySiteID($site_id);
            
            if(!empty($categories)) {

                foreach($categories as $cData) {

                    $category_id = $cData['categoryId'];
                    $name = $cData['categoryName'];
                    $parent_id = $cData['parentId'];
                    $catModel->addCategory($category_id, $name, $parent_id, $site_id); 
                } 
            }
        }
        
        return $categories;
    }
    
    public static function getLogoWH($site_id)
    {
        $response = array(); 
        if(isset($site_id) && !empty($site_id)) {
            $siteModel = new Site(); 
            $siteData = $siteModel->getSites($site_id); 

            $logoData = unserialize($siteData['logo_data']);

            $response = array(
                'logo_width' => (isset($logoData['logo_width'])) ? $logoData['logo_width'] : '',
                'logo_height' => (isset($logoData['logo_height'])) ? $logoData['logo_height'] : ''
            );         
        }
        return $response; 
    }
    
    public static function getFirstSentence($string)
    {
        // First remove unwanted spaces - not needed really
        $string = strip_tags($string); 
        //$string = html_entity_decode($string); 
        $string = str_replace("&nbsp;"," ",$string);
        $string = str_replace(" .",".",$string);
        $string = str_replace(" ?","?",$string);
        $string = str_replace(" !","!",$string);
        // Find periods, exclamation- or questionmarks with a word before but not after.
        // Perfect if you only need/want to return the first sentence of a paragraph.
        preg_match('/^.*[^\s](\.\s+|\?|\!)/Uis', $string, $match);
        return isset($match[0]) ? $match[0] : '';
    } 
    
    public static function concatLogoToDesc($logoWPurl, $description, $advertiser_id, $site_id, $put_desc_logo = false)
    {   
        $descriptionLogo = false;         
        $description = trim($description);

        $urlsModel = new Urls();
        if(!empty($logoWPurl)) {
            //$descriptionLogo = str_replace($search, $replace, $logoWPurl);        
            // addded full size logo to description instead of 150x150
            $descriptionLogo = $logoWPurl;
            $urlsModel->updateData(array('desc_logo_url' => $descriptionLogo, 'id' => $advertiser_id));
        }
        else {
            
            $result = $urlsModel->getUrlsData($advertiser_id);
            $descriptionLogo = (count($result)) ? $result['desc_logo_url'] : false;
        }
        
        $LogoWH = WpAdmin::getLogoWH($site_id); 
        
        $width = !empty($LogoWH['logo_width']) ? $LogoWH['logo_width'] : '150'; 
        $height = !empty($LogoWH['logo_height']) ? $LogoWH['logo_height'] : '150';
        
        if(!empty($descriptionLogo)) {
            
            if($put_desc_logo) {
                
                $preDesc = '<img class="alignleft size-thumbnail" width="'.$width.'" height="'.$height.'" title="toplook" src="'.$descriptionLogo.'">';
                
                //$description = strip_tags($description);
                $description = preg_replace("/<img[^>]+\>/i", "", $description); 
                
                $description = $preDesc.$description; 
            }
            else $description = preg_replace("/<img[^>]+\>/i", "", $description);  //$description = strip_tags($description);
        }
        
        return $description;
    }  
    
    /**
     * assignAllUrlsToCategories
     * @desc: assign all urls (advertisers in local db) to each own category 
     * fill advertiser_category db table
     * @param int site_id - id of site which urls will be assign
     */
    public static function assignAllUrlsToCategories($site_id)
    {
        $advertiserCategoryModel = new AdvertiserCategory(); 
        $catModel = new Categories();
        $urlsModel = new Urls(); 
        $uData = $urlsModel->getDataByFields(array('site_id'=>$site_id), true);
        
        foreach($uData as $val) {
            
            $urls_id = $val['id']; 
            
            $categories_name_arr = @unserialize($val['site_category']);
            $site_id = $val['site_id']; 
            
            $advertiserCategoryModel->assignAdvToCategories($urls_id, $categories_name_arr, $site_id);
            
            if (count($categories_name_arr) > 2) {

                 $new_cats = array();
                 $advCategories = $advertiserCategoryModel->getCatByUrlAndSiteID($urls_id, $site_id);
                 foreach ($categories_name_arr as $url_cat) {

                     $category = $catModel->getCategoryBySiteAndName($site_id, $url_cat);
                     if ($category['parent_id'] != 0) {
                         $root_category = $catModel->getCategoryByID($category['parent_id'],$site_id);
                         $new_cats[] = $root_category['name'];
                         $new_cats[] = $url_cat;
                     } else {
                         if (!$advertiserCategoryModel->checkParent($category['category_id'], $advCategories)) $new_cats[] = $url_cat;
                     }
                 }
                $site_categories_serialize = serialize($new_cats);
                $upData = array(
                    'site_category' => $site_categories_serialize,
                    'id' => $urls_id
                );
                $urlsModel->updateData($upData);
            }
            
        }
        

        
        return true; 
    }
    
    /**
     * saveSiteBlacklist
     * @param int $blacklist_id
     * @param int $site_id 
     * @return boolean true
     */
    public static function saveSiteBlacklist($blacklist_id, $site_id)
    {
        //save black list 
        $sbModel = new SiteBlacklist();

        if(!empty($blacklist_id) &&
                $site_id) {

            $sbData = $sbModel->getDataByFields(array('site_id'=>$site_id));
            if(!empty($sbData)) {
                //updating
                $sbModel->updateData(array('blacklist_id'=>$blacklist_id), array('site_id'=>$site_id));
            } 
            else {
                //inserting
                $sbModel->insertData(array('blacklist_id'=>$blacklist_id, 'site_id'=>$site_id));
            }
        }
        else if(empty($blacklist_id) &&
                $site_id) $sbModel->deleteData(array('site_id'=>$site_id));
        
        return true; 
    }
    
    /**
     * saveAdvertiserBlacklist
     * @param type $blacklist_id
     * @param int $urls_id - advertiser local id
     * @return boolean 
     */
    public static function saveAdvertiserBlacklist($blacklist_id, $urls_id)
    {
        //save black list 
        $abModel = new AdvertiserBlacklist();

        if(!empty($blacklist_id) &&
                $urls_id) {

            $abData = $abModel->getDataByFields(array('urls_id'=>$urls_id));
            if(!empty($abData)) {
                //updating
                $abModel->updateData(array('blacklist_id'=>$blacklist_id), array('urls_id'=>$urls_id));
            } 
            else {
                //inserting
                $abModel->insertData(array('blacklist_id'=>$blacklist_id, 'urls_id'=>$urls_id));
            }
        }       //removing .. 
        else if(empty($blacklist_id) &&
                $urls_id) $abModel->deleteData(array('urls_id'=>$urls_id));
        
        return true; 
    }

    public static function fetchFormattedDomain($hostName)
    {
        return strtolower(rtrim(str_ireplace(array('http://', 'https://', 'www.'), '', $hostName), '/'));
    }
    
    /**
     * sendLogoToWpByRpc
     * @desc send image data by wp xmlrpc api 
     * @param type $site_id
     * @param type $advertiser_id
     * @return array 
     */
    public static function sendLogoToWpByRpc($site_id, $advertiser_id)
    {
        $res = array(); 
        $res['url'] = null;

        // send "metaWeblog.newPost" to WP by XML RPC api 
        $site_id = intval($site_id);
        $objXMLRP = self::getXMLRPobj($site_id);

        // send logo data
        $filename = $advertiser_id.'.jpg';
        $image = dirname(__FILE__).'/../../upload/'.$site_id.'/'.$filename;
        
        if(file_exists($image)) {
            
            $fs = filesize($image);

            $file = fopen($image, 'rb');
            Log::l('sendLogoToWpByRpc image: '.$image);
            $filedata = fread($file, $fs);
            fclose($file);

            $response = $objXMLRP->create_mediaobject($filename, $filedata);

            Log::l('Result of create_mediaobject() rpcapi function : '.$response, Zend_Log::INFO);

            $rObj = simplexml_load_string($response);

            $logoWPRes = isset($rObj->params->param->value->struct->member) ? $rObj->params->param->value->struct->member : array();
            
            if(!empty($logoWPRes)){
                foreach($logoWPRes as $data)    
                    if (isset($data->name) && isset($data->value))
                        $res[(string)$data->name] = (string)$data->value->string;
            } 
        }
        
        return $res;
    }   
    
    public static function setRoleByRpcApi($urls_id, $role)
    {
        $result = false; 
        if($urls_id && $role) {
            
            $siteModel = new Site(); 
            $urlsModel = new Urls(); 
            $uData = $urlsModel->getDataByFields(array('id'=>$urls_id)); 
            
            $site_id = $uData['site_id'];
            
            $localWpPostID = (int)$uData['wp_post_id'];
            $title = $uData['name'];
            $logoWPurl = $uData['desc_logo_url'];
            $description = $uData['description'];
            $advertiser_id = $urls_id; 
            $put_desc_logo = ($uData['desc_logo']==1) ? true : false;
            
            $body = self::concatLogoToDesc($logoWPurl, $description, $advertiser_id, $site_id, $put_desc_logo);
            
            $site_category = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $uData['site_category']);
            $category = @unserialize($site_category);
            $keywords = strtolower($uData['name']);
            
            $sData = $siteModel->getSites($site_id);
            $emailParam = $sData['email'];
            
            $descriptionLogo = $uData['desc_logo_url'];
            $redirectUrl = $uData['redirect_url'];
            $exceptionUrl = $uData['exception_url'];
            $exceptionUrl2 = $uData['exception_url2'];            
            $customfields_edit = self::getEditCustomFields($site_id, $localWpPostID, $emailParam, $redirectUrl, $exceptionUrl, $exceptionUrl2, $descriptionLogo, $role, false);
            
            // send exist image by rpc and resend that received mediaId 
            $logoData = self::sendLogoToWpByRpc($site_id, $advertiser_id);
            $wp_post_thumbnail = isset($logoData['id'])&&!empty($logoData['id']) ? $logoData['id'] : false;            
            
            $excerptDesc = self::getFirstSentence($description);
            
            // set featured_post
            $wp_page_order = ($uData['featured_post']!=0) ? 0 : 2147483647;
            
            $objXMLRP = self::getXMLRPobj($site_id);
            $result = $objXMLRP->edit_post($localWpPostID, $title, $body, $category, $keywords, $customfields_edit, 'UTF-8', $wp_post_thumbnail, $excerptDesc, $wp_page_order);
        }
        
        return $result; 
    }
    
    /**
     * setPublished 
     * @desc set status published or not published to array of advertisers 
     * @param bool $state - false or true hide or show 
     * @param array $advertisers - array of advertisers
     * @param int $site_id 
     * @return mixed  
     */
    
    public static function setPublished($state, $advertisers, $site_id)
    {
        $result = array(); 
        
        $err = ''; 
        
        $objXMLRP = self::getXMLRPobj($site_id);
        $urlsModel = new Urls(); 

        foreach($advertisers as $uItem) {

            
            
            $localWpPostID = $uItem['wp_post_id']; 
            $urls_id = $uItem['id']; 
            try {
                // set state in the minisite
                $response = $objXMLRP->set_published($localWpPostID, $state);
                $result = @simplexml_load_string($response);
            } 
            catch (Exception $e) {
                $err = 'Exception: '.$e; 
            }
            
            if($err == '') {
                // set state in the db 
                $urlsModel->updateData(array(
                    'published' => $state,
                    'id' => $urls_id
                ));
            }
        }      
        
        return $result; 
    }
}

?>

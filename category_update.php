<?php

function dump($data) {
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

function checkExistAdv($realName, $urls_id, $site_id) 
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

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/admin/libs'),
    get_include_path(), //uncomment for developer environment only
)));

require_once 'admin/includes/bootstrap.php';

$siteModel = new Site();
$sites = $siteModel->getSites();
$s = 0;
$c = 0;
foreach ($sites as $site) {

    $site_id = $site["id"];

    $objXMLRP = WpAdmin::getXMLRPobj($site_id);
    $catModel = new Categories();
    $allcats = $catModel->getCategoriesBySiteID($site_id);

    $urlsModel = new Urls();
    $aData = $urlsModel->getTableData($site_id, 1);
    $exist = false;
    foreach ($aData as $advItem) {

        $data = array();
        $update = false;
        $url_id = $advItem['id'];

        $localWpPostID = false;
        // check existing advertiser in local db and get that wp_post_id
        $localWpPostID = checkExistAdv($advItem['name'], $advItem['id'], $site_id);
        $advcatModel = new AdvertiserCategory();
        $advCategories = $advcatModel->getCatByUrlAndSiteID($url_id, $site_id); 

        //is_root or is_parent
        foreach ($advCategories as $catItem) {
            
            if(($catItem['parent_id'] != 0) && !$advcatModel->checkRoot($catItem['parent_id'], $advCategories)) {
                $update = true;
                if (!in_array($catItem['name'],$data)) $data[] = $catItem['name'];
                $parent = $catModel->getCategoryByID($catItem['parent_id'], $site_id);
                if (!in_array($parent['name'],$data)) $data[] = $parent['name'];
                /*foreach ($advCategories as $cat) {
                    if ($cat["parent_id"]==$catItem['category_id']) $data[] = $cat["name"];
                }*/
                
                $c++;
                //break;
            } else {
                if (!in_array($catItem['name'],$data)) $data[] = $catItem['name'];
            }
        }

        if ($update) {
            echo $c.") ".$site["id"].":".$site["domain"]." - ".$advItem["id"].":".$advItem["name"]." - ".implode(";",$data)."<br/>";
            $exist = true;
            // managing adv categories
            $site_categories_serialize = serialize($data);
            $advertiserCategoryModel = new AdvertiserCategory(); 
            $advertiserCategoryModel->assignAdvToCategories($url_id, $data, $site_id);

            // update site_category field in urls table
            $urls = new Urls();
            $upData = array(
                'site_category' => $site_categories_serialize,
                'id' => $url_id
            );
            $urls->updateData($upData);

            // set categories_tree field for current advertiser
            $categories_tree = $advertiserCategoryModel->getCategoriesByAdvID($url_id); //categories_tree
            $upTreeData = array('categories_tree' => $categories_tree, 'id' => $url_id);
            $urls->updateData($upTreeData);
            if (intval($localWpPostID) && !is_null($localWpPostID) && !empty($localWpPostID)) {
                //$body = WpAdmin::concatLogoToDesc($advItem['desc_logo_url'], $advItem['description'], $url_id, $site_id, $advItem['desc_logo']);
                //$response = $objXMLRP->edit_post($localWpPostID, $advItem['name'], $body, $data);
                $response = $objXMLRP->update_categories($localWpPostID, $data);
            }
            
        }
        
    }
//    if ($exist) $s++;
//    if ($s>1) break;
//    break; 
}
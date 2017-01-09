<?php

function get_size($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    return $size;
}
function dump($data) {
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}
set_time_limit(0);
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/admin/libs'),
    get_include_path(), //uncomment for developer environment only
)));

require_once 'admin/includes/bootstrap.php';

$Urls = new Urls();
$advers = $Urls->getTableData();
$sql = 'DELETE FROM black_logos';
$smtm = app::inst()->db->prepare($sql);
$smtm->execute();
$siteModel = new Site();
$data = array();
foreach ($advers as $adver) {
    if ($adver['desc_logo_url']) {
        $size = get_size($adver['desc_logo_url']);
        if ($size==1439) {
            $site = $siteModel->getSitebyDomain($adver['domain']);
            $sql = "INSERT INTO black_logos SET `site_id`= :site_id, `url_id`= :url_id";
            $insData = array(
                ':site_id' => $site['id'],
                ':url_id' => $adver['id']
            );
            $smtm = app::inst()->db->prepare($sql);
            $result = $smtm->execute($insData);
            $data[] = $adver['domain']."    ".$adver['name'];
        }
    }
}
if (!empty($data)) {
    $settingsModel = new Settings();
    $system_email = $settingsModel->getSettingByKey('system_email');

    $to = "if@trafficjunction.co.uk";
    $from = ($system_email) ? $system_email : 'wpredirects@system.com';
    $subject = 'WP advertisers with black logo images';

    $body = '';
    foreach ($data as $item) {$body .= $item.PHP_EOL;}
    
    // send 
    $result = Mailer::sendHtmlMail($from, $to, $subject, $body);
}
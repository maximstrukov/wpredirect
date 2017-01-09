<?php
/*
 * Script for assembling statistic data to single full db table. 
 */

// INIT
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/libs'),
    get_include_path(), //uncomment for developer environment only
)));
require_once 'includes/bootstrap.php';

// script for fill in statistic db table

$statisticModel = new Statistic(); 
$start_point = $statisticModel->getStartPoint(); 

$urlslogsModel = new UrlsLogs(); 
$minSQL = ($start_point) ? ' id > '.$start_point : ' id > 0 '; 
$minDate = $urlslogsModel->getCustomData($minSQL, array('MIN(added_date) as min'), false);

$min = trim($minDate['min']);

// get all dates 
$datesData = $urlslogsModel->getAddedDates($min);

foreach ($datesData as $c_date) {
    
    usleep(100000); // sleep 1/10 of sec
    
    $urlsLogsData = $urlslogsModel->getStatisticByDate($c_date['added_date'], $start_point);
    
    $aData = array();
    foreach ($urlsLogsData as $row) {
        
        $aData[$row['url_id'].$row['added_date']] = array(
            'urls_logs_id' => $row['urls_logs_id'],
            'added_date' => $row['added_date'],
            'name' => $row['name'],
            'url_id' => $row['url_id'], 
            'redirect' => ($row['type'] == 'redirect') ? $row['cnt'] : ((isset($aData[$row['url_id'].$row['added_date']]['redirect'])) ? $aData[$row['url_id'].$row['added_date']]['redirect'] : ''),
            'exception' => ($row['type'] == 'exception') ? $row['cnt'] : ((isset($aData[$row['url_id'].$row['added_date']]['exception'])) ? $aData[$row['url_id'].$row['added_date']]['exception'] : ''),
            'site_id' => $row['site_id']
        );
    }    
    
    foreach($aData as $sItem) {
        $statisticModel->insertData($sItem);
    }
}

echo 'Done!';
?>

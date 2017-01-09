<?php

class StatController extends BaseController {
    
    
    public function ipinfo2Action() 
    {     
        set_time_limit(0);
        
        $_list = (isset($_REQUEST['list']) && !empty($_REQUEST['list'])) ? $_REQUEST['list'] : false;
        $_site = (isset($_REQUEST['site']) && !empty($_REQUEST['site'])) ? $_REQUEST['site'] : false;
        
        // set csv header
        $filename = 'ipsforbl'.date('Y-m-d_h:i:s', time());
        header("Content-type: csv/plain"); 
        header("Content-Disposition: attachment; filename=$filename.csv");
        echo 'IP;'; 
        echo 'Advertiser;'; 
        echo 'WPSite;'; 
        echo 'URL'.PHP_EOL;
        
        // get files 
        $dir = dirname(__FILE__).'/../logs/';
        $dh = opendir($dir);        
        
        while($filename = readdir($dh)) {
            
            if(strpos($filename, 'HandlerController')) {
                
                $file = dirname(__FILE__).'/../logs/'.$filename;
                
                // Read a file by-line 
                $handle = fopen ($file, "r");
                
                $block = false;
                $collection = ''; 
                while (!feof ($handle)) { 
                    
                    // line from file
                    $buffer = fgets($handle, 4096);
                    
                    if(strpos($buffer,'started at')) $block = true; 
                    else if(strpos($buffer,'ended at')) $block = false; 
                    
                    $collection .= $buffer;
                    
                    if(!$block) {
                        
                        //here to parsing of collection 
                        
                        $store = false; // store records or not 
                        $advertiser = false; // advertiser name
                        $user_ip = false; // user ip
                        $http_host = false; // http_host of site form user came; 
                        $isRedirect = 'T'; // Is redirect to Tracking or Competitor url;  0  -T, 1 - C
                        
                        // get redirect status 
                        $resultArr = explode('Result (response to wp-minisite):', $collection); 

                        unset($resultArr[0]);
                        if(isset($resultArr[1])) {

                            $jsonStatus = substr($resultArr[1], 0, strpos($resultArr[1], PHP_EOL)); 
                            $status = json_decode($jsonStatus, true);
                            if(!$status['exception_url']) {
                                $isRedirect = 'C';
                            }
                        }                        
                        
                        // get advertiser name and exception_link
                        $fetchings = explode('Fetching data is: ', $collection); 
                        
                        unset($fetchings[0]); // delete first row
                        
                        foreach($fetchings as $fetch) {
                            
                            $fArr = substr($fetch, 0 , strpos($fetch, PHP_EOL));

                            $advData = json_decode($fArr, true); 

                            if(count($advData)>14) {

                                if(!isset($advData['name']) || !isset($advData['exception_url'])) continue;

                                $advertiser = $advData['name'];
                                $tracking_url = $advData['exception_url'];
                                
                                if($_list) {
                                    
                                    $checkUrls = explode(',', $_list);
                                    foreach($checkUrls as $cUrl) {

                                        if(strpos($tracking_url, $cUrl)) {
                                            $store = true; 
                                        }
                                    }
                                }                            
                            }               
                        }
                        
                        // get user_ip and http_host 
                        $rPostData = explode('Received Post data:', $collection); 
                        unset($rPostData[0]); // delete first row                        
                        
                        $advData = isset($rPostData[1]) ? $rPostData[1] : '';
                        
                        $rPostJson = substr($advData, 0, strpos($advData, PHP_EOL));
                        $rPostArr = json_decode($rPostJson, true); 
                        $user_ip = isset($rPostArr['user_ip']) ? $rPostArr['user_ip'] : false; 
                        $http_host = isset($rPostArr['http_host']) ? $rPostArr['http_host'] : false;

                        // compare http_host with get site
                        if($_site) {
                            if($_site != $http_host)
                                $http_host = false; 
                        }                        
                       
                        // output data 
                        if($store && 
                                $user_ip && 
                                    $http_host) {

                            echo ''.$user_ip.';'; 
                            echo '"'.$advertiser.'";'; 
                            echo '"'.$http_host.'";';
                            echo ''.$isRedirect.PHP_EOL;
                        }                        
                        
                        // cleaning collection
                        $collection = ''; 
                    }
                } 
                
                fclose($handle);
            }
        }
        
        die(PHP_EOL.'Done');
    }
    
    /**
     *@desc example of calling http://hostname.host/admin/index.php?cont=stat&act=ipinfo&list=click.linksynergy.com
     */
    public function ipinfoAction()
    { 
        set_time_limit(0); 
        
        $_list = (isset($_REQUEST['list']) && !empty($_REQUEST['list'])) ? $_REQUEST['list'] : false; 
        $_site = (isset($_REQUEST['site']) && !empty($_REQUEST['site'])) ? $_REQUEST['site'] : false;
        
        // export to csv
        $filename = 'ipsforbl'.date('Y-m-d_h:i:s', time());
        header("Content-type: csv/plain"); 
        header("Content-Disposition: attachment; filename=$filename.csv");
        echo 'IP;'; 
        echo 'Advertiser;'; 
        echo 'WPSite;'; 
        echo 'URL'.PHP_EOL;
       
        // get files 
        $dir = dirname(__FILE__).'/../logs/'; 
        $dh = opendir($dir);
        
        while($filename = readdir($dh)) {
            
            if(strpos($filename, 'HandlerController')) {
                
                //echo $filename.'<br />';
                
                $file = dirname(__FILE__).'/../logs/'.$filename; 
                $content = file_get_contents($file);
                
                $receivedPosts = explode('Received Post data:', $content);
                
                unset($receivedPosts[0]); // delete first row
                
                foreach($receivedPosts as $rPost) {
                
                    $store = false; // store records or not 
                    $advertiser = false; // advertiser name
                    $user_ip = false; // user ip
                    $http_host = false; // http_host of site form user came; 
                    $isRedirect = 'T'; // http_host of site form user came;  0  -T, 1 - C

                    // get redirect status 
                    //$jsonStatus = substr($rPost, strpos($rPost, ));
                    $resultArr = explode('Result (response to wp-minisite):', $rPost); 
                    unset($resultArr[0]);
                    if(isset($resultArr[1])) {
                        
                        $jsonStatus = substr($resultArr[1], 0, strpos($resultArr[1], PHP_EOL)); 
                        $status = json_decode($jsonStatus, true); 
                        if(!$status['exception_url']) {
                            $isRedirect = 'C';
                        }
                    }
                    
                    // get advertiser name and exception_link
                    $fetchings = explode('Fetching data is: ', $rPost); 
                    unset($fetchings[0]); // delete first row
                    
                    foreach($fetchings as $fetch) {
                        
                        $fArr = substr($fetch, 0 , strpos($fetch, PHP_EOL));
                        
                        $advData = json_decode($fArr, true); 

                        if(count($advData)>14) {
                           
                            if(!isset($advData['name']) || !isset($advData['exception_url'])) continue;
                            
                            $advertiser = $advData['name'];
                            $tracking_url = $advData['exception_url'];
                            
                            //1. адвертисеры у которых в tracking url есть click.linksynergy.com
                            //2. адвертисеры у которых в tracking url есть kqzyfj.com or jdoqocy.com or  tkqlhce.com or anrdoezrs.net or dpbolvw.net                           
                            
                            if($_list) {
                                
                                //$checkUrls = explode('|', $_list);
                                $checkUrls = explode(',', $_list);
                                foreach($checkUrls as $cUrl) {
                                    
                                    if(strpos($tracking_url, $cUrl)) {
                                        $store = true; 
                                    }
                                }
                            }                            
                        }               
                    }
                    
                    // get user_ip and http_host 
                    $rPostJson = substr($rPost, 0, strpos($rPost, PHP_EOL));
                    $rPostArr = json_decode($rPostJson, true); 
                    $user_ip = isset($rPostArr['user_ip']) ? $rPostArr['user_ip'] : false; 
                    $http_host = isset($rPostArr['http_host']) ? $rPostArr['http_host'] : false;
                    
                    // compare http_host with get site
                    if($_site) {
                        if($_site != $http_host)
                            $http_host = false; 
                    }
                    
                    // output data 
                    if($store && 
                            $user_ip && 
                                $http_host) {
                        
                        echo ''.$user_ip.';'; 
                        echo '"'.$advertiser.'";'; 
                        echo '"'.$http_host.'";';
                        echo ''.$isRedirect.PHP_EOL;
                    }
                }
            }
        }
        
        die(PHP_EOL.'Done');
    }
    
    function statAction() 
    {
        $this->registerScriptFile('js/app.stat.js');
        
        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;
        $dates = array();

        for ($d = 0; $d < 30; $d++) {
            $dates[] = date('Y-m-d', strtotime('-' . $d . ' day'));
        }
        
        $urlsModel = new Urls(); 
        $result = $urlsModel->getDataByFields(array(), true, array('name'));

        $campaigns = array();
        foreach($result as $row){
            $campaigns[$row['id']] = $row;
        }

        $this->render('stat', array(
            'campaigns'=>$campaigns,
            'dates'=>$dates,
            'campaign_id'=>$campaign_id,
            'start'=>$start,
            'end'=>$end,
        ));
    }

    public function stattableAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;

        $campaign_id_sql = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? ' and url_id=' . intval($_REQUEST['campaign_id']) : '';


        $sql = "Create temporary table tmp_urls_dates SELECT added_date FROM urls_logs 
                where added_date between :start and :end
                group by added_date";

        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));
        
        $sql = "create temporary table tmp_urls_redirects SELECT count(*) as redirects, added_date FROM urls_logs 
                where added_date between :start and :end and type='redirect' " . $campaign_id_sql . "
                group by added_date";
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));
        
        $sql = "create temporary table tmp_urls_exceptions SELECT count(*) as exceptions, added_date FROM urls_logs 
                where added_date between :start and :end and type='exception'  " . $campaign_id_sql . "
                group by added_date";
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));
        
        $sql = "select tmp_urls_dates.*, tmp_urls_redirects.redirects, tmp_urls_exceptions.exceptions from tmp_urls_dates 
                left join tmp_urls_redirects on tmp_urls_redirects.added_date = tmp_urls_dates.added_date
                left join tmp_urls_exceptions on tmp_urls_exceptions.added_date = tmp_urls_dates.added_date";
        
        $result = app::inst()->db->query($sql)->fetchAll();
        
        $resArr = array();
        foreach($result as $row){
            $rRow[0] = $row['added_date'];

            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($campaign_id) . '&type=redirect';

            $rRow[1] = $row['redirects'] ? '<a href="' . $url . '" target="_blank">' . $row['redirects'] . '</a>' : $row['redirects'];

            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($campaign_id) . '&type=exception';
            $rRow[2] = $row['exceptions'] ? '<a href="' . $url . '" target="_blank">' . $row['exceptions'] . '</a>' : $row['exceptions'];


            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }

    public function statdetailsAction() 
    {
        $this->registerScriptFile('js/app.statdetails.js');
        
        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));
        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;
        
        $this->registerScript('statdetails', 'var type = "'.(isset($_REQUEST['type']) ? $_REQUEST['type'] : '').'"; 
              var start = "'.$start.'"; var end = "'.$end.'"; var campaign_id = "'.$campaign_id.'";');
        
        $type = in_array($_REQUEST['type'], array('redirect', 'exception')) ? $_REQUEST['type'] : '';
        //  print_r($dates);
        //die;

        $this->render('statdetails', array(
            'start'=>$start,
            'end'=>$end,
            'campaign_id'=>$campaign_id,
            'type'=>$type,
        ));
    }

    function statdetailstableAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $campaign_id_sql = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? ' and url_id='.  intval($_REQUEST['campaign_id']) : '';

        $type_sql = in_array($_REQUEST['type'], array('redirect', 'exception')) ? ' and `type` = \''.$_REQUEST['type'].'\' ' : '';

        $sql = "SELECT urls_logs.*, 
            urls_logs.isp_name, urls_logs.country_code, up.urls_id, INET_NTOA(urls_logs.start_ip) as start_ip,  INET_NTOA(urls_logs.end_ip) as end_ip
                FROM urls_logs 
                inner join urls on urls_logs.url_id = urls.id
                left join urls_providers up on up.urls_id = urls_logs.url_id and up.provider_name = urls_logs.isp_name
                where urls_logs.added_date between :start and :end " . $campaign_id_sql . " " . $type_sql . "
                group by urls_logs.id                    
                order by added desc
                limit 2000";
        
        $sqlParams = array(
            ':start' => $start,
            ':end' => $end
            //':type' => $type,  
            //':campaign_id' => $campaign_id,  
        );         
//        echo '<pre>';
//        print_r($arr);
//        die($sql);
        
//        $type = (!empty($_REQUEST['type'])) ? $_REQUEST['type'] : '';
//        $campaign_id = (!empty($_REQUEST['campaign_id'])) ? $_REQUEST['campaign_id'] : '';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($sqlParams);
        $result = $smtm->fetchAll();
        
        $resArr = array();

        foreach($result as $row){
//            $rRow[0] = $row['id'];
            $rRow[0] = $row['added'];

            $rRow[1] = '<a href="http://domaintools.com/' . long2ip($row['remote_ip']) . '" target="_blank">' . long2ip($row['remote_ip']) . '</a>';

            $rRow[2] = $row['isp_name'].'('.$row['country_code'].') '.$row['start_ip'].'-'.$row['end_ip'];
            if(!$row['urls_id']){
                $rRow[2] .= ' <a title="Add IP to Exceptions" href="#" onclick="addToexeption(\'' . ($row['url_id']) . '\',\'' . ($row['country_code']) . '\',\'' . ($row['isp_name']) . '\');return false;"><span class="ui-icon ui-icon-circle-plus"></span></a>';
            }
            $rRow[3] = $row['user_agent'];

            $resArr[] = $rRow;
        }

        $response = array('aaData' => $resArr);

        echo json_encode($response);
        exit();
    }

    function statcampAction() 
    {        
        $this->registerScriptFile('js/app.statcamp.js');
     
        //added rGraph library 
        $this->registerScriptFile('js/RGraph/libraries/RGraph.common.core.js');
        $this->registerScriptFile('js/RGraph/libraries/RGraph.line.js');
        
        $start = false; 
        $end = false; 
        $settime = false; 
        
        if(isset($_REQUEST["range"]) && !empty($_REQUEST["range"])) {
            
            $daterange = explode(" - ", $_REQUEST["range"]);
            
            if (count($daterange) === 1) {

                $start = $daterange[0];  
            }
            else {  

                $start = $daterange[0];  
                $end = $daterange[1];
            }
        }
        
        $start = date('Y-m-d', (empty($start)) ? strtotime('-1 day') : strtotime($start));
        $end = date('Y-m-d', (empty($end)) ? strtotime('now') : strtotime($end));
        //echo $start.'<br />'.$end;
        $settime = $start." - ".$end;

        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;
        $site_id = (!empty($_REQUEST['site_id']) && intval($_REQUEST['site_id']) > 0) ? intval($_REQUEST['site_id']) : false;

        $dates = array();

        for ($d = 0; $d < 30; $d++) {
            $dates[] = date('Y-m-d', strtotime('-' . $d . ' day'));
        }

        $urlsModel = new Urls(); 
        
        $result = $urlsModel->getTableData($site_id);
        
        $campaigns = array();

        foreach($result as $row) {
            if(!empty($row['domain']))
                $campaigns[$row['id']] = $row;
        }  
        
        // counting of redirects for 'Competitor Visits' and 'Advertiser Visits' columns
        //$urlLogsModel = new UrlsLogs(); 
        
        $url_id = (isset($_REQUEST['campaign_id']) && $_REQUEST['campaign_id']!='-1') ? $_REQUEST['campaign_id'] : false ;

        // for use direct UrlsLogs model 
//        $redirectsData = $urlLogsModel->getVisitsCnt($start, $end, $url_id, $site_id, 'redirect');
//        $redirects = $redirectsData[0]['cnt'];
        
        $statisticModel = new Statistic(); 
        $redirectsData = $statisticModel->getVisitsCnt($start, $end, $url_id, $site_id, 'redirect');
        $redirects = $redirectsData;
        
        $redirectsData = $statisticModel->getVisitsCnt($start, $end, $url_id, $site_id, 'exception');
        $exception = $redirectsData;
        
        // get site list
        $siteModel = new Site(); 
        $allSites = $siteModel->getSites();      
        
        // get rGraph data 
        //$rGraph = ($site_id>0) ? $urlLogsModel->getVisitsCnt($start, $end, $url_id, $site_id, false, true) : array();
        // use statisctic model 
        // output data by day
        $rGraph = ($site_id>0) ? $statisticModel->getVisitsCnt($start, $end, $url_id, $site_id, false, true) : array();
        
        if(count($rGraph)>46) {
            // output data by month
             $rGraph = ($site_id>0) ? $statisticModel->getVisitsCnt($start, $end, $url_id, $site_id, false, false, true) : array();
        } 
        
    //        $rGraph[] = array('cnt' => 1, 'added_date' => '2013-10-25'); 
    //        $rGraph[] = array('cnt' => 2, 'added_date' => '2013-10-15'); 
    //        $rGraph[] = array('cnt' => 1, 'added_date' => '2013-10-10'); 
        
        $this->render('statcamp', array(
            'rGraph'=>$rGraph,
            'campaigns'=>$campaigns,
            'dates'=>$dates,
            'campaign_id'=>$campaign_id,
            'site_id'=>$site_id,
            'start'=>$start,
            'end'=>$end,
            'settime' => $settime, 
            'redirects' => $redirects, 
            'exception' => $exception,
            'all_sites' => $allSites
        ));
    }
    
    public function statcamptableAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;
        $campaign_id_sql = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? ' and urls_logs.url_id=' . intval($_REQUEST['campaign_id']) : '';
        
        $site_id = (!empty($_REQUEST['site_id']) && intval($_REQUEST['site_id']) > 0) ? intval($_REQUEST['site_id']) : -1;
        $filter_by_site_sql = ($site_id>0) ? ' INNER JOIN urls ON urls.site_id = '.$site_id.' AND urls.id = urls_logs.url_id ' : '';

        $sql = "Create temporary table IF NOT EXISTS tmp_urls_dates 
                    SELECT 
                        urls_logs.added_date, 
                        urls_logs.url_id 
                    FROM urls_logs 
                    $filter_by_site_sql
                    where urls_logs.added_date between :start and :end " . $campaign_id_sql . "
                    group by urls_logs.added_date, urls_logs.url_id";
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));
        
        //Total rows
        $sql = "SELECT FOUND_ROWS() as cnt"; 
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute();
        $cData = $smtm->fetch();
        $iFilteredTotal = $cData['cnt'];
        $iTotal = $iFilteredTotal; 
        
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )            
		$sLimit = " LIMIT ".$_GET['iDisplayStart'].", ".$_GET['iDisplayLength'];

        $sql = "create temporary table IF NOT EXISTS tmp_urls_redirects 
                    SELECT 
                        count(*) as redirects, 
                        urls_logs.added_date, 
                        urls_logs.url_id 
                    FROM urls_logs 
                    $filter_by_site_sql
                    where urls_logs.added_date between :start and :end and urls_logs.type='redirect' " . $campaign_id_sql . " 
                    group by urls_logs.added_date, urls_logs.url_id ".$sLimit;
        //die($sql); 
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));

        $sql = "create temporary table IF NOT EXISTS tmp_urls_exceptions 
                    SELECT 
                        count(*) as exceptions, 
                        urls_logs.added_date, 
                        urls_logs.url_id 
                    FROM urls_logs 
                    $filter_by_site_sql
                    where urls_logs.added_date between :start and :end and urls_logs.type='exception'  " . $campaign_id_sql . "
                    group by urls_logs.added_date, urls_logs.url_id ".$sLimit;
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':start' => $start,
            ':end' => $end
        ));
        
	/* 
	 * Filtering        
	 */
        //the columns are to be searched
        $aColumns = array('tmp_urls_dates.added_date', 'urls.name', 'tmp_urls_redirects.redirects', 'tmp_urls_exceptions.exceptions');
	$sWhere = "";
	if ( $_GET['sSearch'] != "" ) {
            
		$sWhere = "WHERE (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
                    $sWhere .= $aColumns[$i]." LIKE '%".$_GET['sSearch']."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
        
	/*
	 * Ordering
	 */
        $sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) ) {
            
            $sOrder = "ORDER BY  ";
            for ( $i=0; $i<intval( $_GET['iSortingCols'] ); $i++ ) {

                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" ) {

                        $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                                ".$_GET['sSortDir_'.$i].", ";
                }
            }
		
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" ) {
                $sOrder = "";
            }
	}
        
        $sql = "SELECT 
                    tmp_urls_dates.*, 
                    tmp_urls_redirects.redirects, 
                    tmp_urls_exceptions.exceptions, 
                    urls.name as campaign, 
                    urls.id as campaign_id 
                FROM tmp_urls_dates 
                LEFT JOIN tmp_urls_redirects ON tmp_urls_redirects.added_date = tmp_urls_dates.added_date AND tmp_urls_dates.url_id = tmp_urls_redirects.url_id
                LEFT JOIN tmp_urls_exceptions ON tmp_urls_exceptions.added_date = tmp_urls_dates.added_date AND tmp_urls_dates.url_id = tmp_urls_exceptions.url_id
                LEFT JOIN urls ON tmp_urls_dates.url_id = urls.id "
                .$sWhere
                .$sOrder
                .$sLimit;
        
        $result = app::inst()->db->query($sql)->fetchAll();        
        $resArr = array();
        
        
        
        foreach($result as $row) {
            
            $rRow[0] = $row['added_date'];
            $rRow[1] = "<a href='?cont=campaign&act=index&id=".$row['campaign_id']."' target='new'>".$row['campaign']."</a>";

            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($row['campaign_id']) . '&type=redirect';

            $rRow[2] = $row['redirects'] ? '<a href="' . $url . '" target="_blank">' . $row['redirects'] . '</a>' : $row['redirects'];

            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($row['campaign_id']) . '&type=exception';
            $rRow[3] = $row['exceptions'] ? '<a href="' . $url . '" target="_blank">' . $row['exceptions'] . '</a>' : $row['exceptions'];

            $resArr[] = $rRow;
        }

        $response = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,            
            'aaData' => $resArr
        );

        echo json_encode($response);
        exit();
    }
    
    /**
     * method statcamptableoptimizedAction
     * @desc new optimized version of statcamptableAction
     */
    public function statcamptableoptimizedAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $url_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : false;
        $site_id = (!empty($_REQUEST['site_id']) && intval($_REQUEST['site_id']) > 0) ? intval($_REQUEST['site_id']) : false;
        
        $search = ($_GET['sSearch'] != "") ? $_GET['sSearch'] : false; 
        
        $urlLogsModel = new UrlsLogs();
        $iTotal = $urlLogsModel->getTotalRowCnt($start, $end, $url_id, $site_id, $search);
        $iFilteredTotal = $iTotal; 
        
        $sData = $urlLogsModel->getStatisticData($start, $end, $url_id, $site_id, $search);
        
        $aData = array();
        foreach($sData as $row) {
            
            $type = ($row['type'] == 'redirect') ? 'redirect' : 'exception'; 
            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($row['url_id']) . '&type='.$type;
            $aData[$row['url_id'].$row['added_date']] = array(
                0 => $row['added_date'], 
                1 => "<a href='?cont=campaign&act=index&id=".$row['url_id']."' target='new'>".$row['name']."</a>", 
                2 => ($row['type'] == 'redirect') ? '<a href="' . $url . '" target="_blank">' . $row['cnt'] . '</a>' : ((isset($aData[$row['url_id'].$row['added_date']][2])) ? $aData[$row['url_id'].$row['added_date']][2] : ''),
                3 => ($row['type'] == 'exception') ? '<a href="' . $url . '" target="_blank">' . $row['cnt'] . '</a>' : ((isset($aData[$row['url_id'].$row['added_date']][3])) ? $aData[$row['url_id'].$row['added_date']][3] : '')
            ); 
        }        
        
        $aData = array_values($aData); 
        
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )     
                $aData = array_splice($aData, $_GET['iDisplayStart'],$_GET['iDisplayLength']);
		//$sLimit = " LIMIT ".$_GET['iDisplayStart'].", ".$_GET['iDisplayLength'];                

        $response = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            'aaData' => $aData
        );
        
        echo json_encode($response);
        exit();        
    }
    /**
     * action statcamptablebuff
     * @desc use buffer statistic data for output statistic (use collect statistic db table)
     */
    public function statcamptablebuffAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $start = date('Y-m-d', (empty($_REQUEST['start'])) ? strtotime('-1 day') : strtotime($_REQUEST['start']));
        $end = date('Y-m-d', (empty($_REQUEST['end'])) ? strtotime('now') : strtotime($_REQUEST['end']));

        $url_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : false;
        $site_id = (!empty($_REQUEST['site_id']) && intval($_REQUEST['site_id']) > 0) ? intval($_REQUEST['site_id']) : false;
        
        $search = ($_GET['sSearch'] != "") ? $_GET['sSearch'] : false;
        
        $statisticModel = new Statistic();
        
        $iTotal = $statisticModel->getTotalRowCnt($start, $end, $url_id, $site_id, $search);
        $iFilteredTotal = $iTotal;         
        
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )     
                //$aData = array_splice($aData, $_GET['iDisplayStart'],$_GET['iDisplayLength']);
		$sLimit = " LIMIT ".$_GET['iDisplayStart'].", ".$_GET['iDisplayLength'];        
        
        $aColumns = array('added_date', 'name', 'redirect', 'exception');

        
	/*
	 * Ordering
	 */
        $sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) ) {
            
            $sOrder = "ORDER BY  ";
            for ( $i=0; $i<intval( $_GET['iSortingCols'] ); $i++ ) {

                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" ) {

                        $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                                ".$_GET['sSortDir_'.$i].", ";
                }
            }
		
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" ) {
                $sOrder = "";
            }
	}        
        $sData = $statisticModel->getStatisticData($start, $end, $url_id, $site_id, $search, $sOrder, $sLimit);
        
        $aData = array(); 
        foreach($sData as $row) {
            
            $rRow[0] = $row['added_date'];
            $rRow[1] = "<a href='?cont=campaign&act=index&id=".$row['url_id']."' target='new'>".$row['name']."</a>";            
            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($row['url_id']) . '&type=redirect';
            $rRow[2] = $row['redirect'] ? '<a href="' . $url . '" target="_blank">' . $row['redirect'] . '</a>' : $row['redirect'];
            $url = '?cont=stat&act=statdetails&start=' . urlencode($row['added_date']) . '&end=' . urlencode($row['added_date']) . '&campaign_id=' . urlencode($row['url_id']) . '&type=exception';
            $rRow[3] = $row['exception'] ? '<a href="' . $url . '" target="_blank">' . $row['exception'] . '</a>' : $row['exception'];
            $aData[] = $rRow; 
        }           
        
        $response = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            'aaData' => $aData
        );
        
        echo json_encode($response);
        exit();                
    }

    public function updateipAction() 
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');    

        $campaign_id = (!empty($_REQUEST['campaign_id']) && intval($_REQUEST['campaign_id']) > 0) ? intval($_REQUEST['campaign_id']) : -1;
        $addedIP = ip2long($_REQUEST['ip']);

        if ($campaign_id > 0 && $addedIP > 0) {
            
            $urlsModel = new Urls();
            $row = $urlsModel->getUrlsData($campaign_id);

            $ips = $row['ips'];

            $tmp_ips = preg_replace('/\\s+-\\s+/s', ' ', $ips);

            $ips_array = preg_split("/[\s,;]+/", trim($tmp_ips));

            $addedFlag = false;

            foreach ($ips_array as $ip) {
                if (ip2long($ip) == $addedIP) {
                    $addedFlag = true;
                    echo json_encode('already added');
                    exit();
                    break;
                }
            }

            if (!$addedFlag)
                $ips.="\n" . $_REQUEST['ip'];

            $ips = (isset($ips)) ? $ips : '';
            $urlsModel->updateData(array(
                'ips' => $ips,
                'id' => $campaign_id
            )); 
            
            echo json_encode('added');
            exit();
        }
        
        echo json_encode('error');
        exit();
    }
    
    public function statisticAction() 
    {
        set_time_limit(0);
        
        $result = array();
        $url_id = isset($_REQUEST['url_id']) ? $_REQUEST['url_id'] : '';
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : '';
        //$url_id = 48;
        
        if(!empty($url_id) && 
                intval($url_id)) {
        
            $sql = "
                SELECT 
                    INET_NTOA(urls_logs.remote_ip) AS ip, 
                    urls_iptable.isp_name AS isp, 
                    urls_logs.added AS date
                FROM urls_logs
                INNER JOIN urls_iptable ON urls_logs.remote_ip BETWEEN urls_iptable.start_ip AND urls_iptable.end_ip
                -- INNER JOIN urls_iptable ON urls_logs.remote_ip > urls_iptable.start_ip AND urls_logs.remote_ip < urls_iptable.end_ip
                WHERE urls_logs.url_id = :url_id 
                -- AND urls_logs.added > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY ip,date
                ORDER BY urls_logs.added DESC
                -- LIMIT 3
            ";
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute(array(
                ':url_id' => $url_id 
            ));
            $result = $smtm->fetchAll();
        }
        
        $siteModel = new Site(); 
        $allSites = $siteModel->getSites(); 
        
        $startSiteID = !empty($site_id) ? $site_id : (!empty($allSites[0]['id']) ? $allSites[0]['id'] : 1);
        
        $urlsModel = new Urls(); 
        $advertisers = $urlsModel->getDataByFields(array('site_id'=>$startSiteID), true);
        
        $this->render('statistic', array(
            'url_id'=>$url_id,
            'site_id'=>$site_id,
            'advertisers'=>$advertisers,
            'all_sites'=>$allSites,
            'result'=>$result
        ));        
    }
    
    public function geturlsbysiteidAction() 
    {
        //$advertisers = array();
        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false; 
        
        $urlsModel = new Urls(); 
//        if($site_id) {
//            
//            $advertisers = $urlsModel->getDataByFields(array('site_id'=>$site_id), true);
//        }
        
        $result = $urlsModel->getTableData($site_id,null, null, 'adver.id,adver.name');
        $campaigns = array();
        foreach($result as $row) {
            if(!empty($row['domain']))
                //$campaigns[$row['id']] = $row;
                $campaigns[] = $row;
        }          
        //$advertisers = $campaigns; 
//        echo '<pre>'; 
//        print_r($campaigns);
//        die(); 
        
        echo json_encode($campaigns); 
        exit();
    }

}
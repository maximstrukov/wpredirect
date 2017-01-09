<?php
/*
 * Script findin matches from advertisers on http://www.helium.com/ 
 * and advertisers from wp_redirect system
 * (Search for duplicate advertisers in the helium)
 */

// INIT
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/libs'),
    get_include_path(), //uncomment for developer environment only
)));
require_once 'includes/bootstrap.php';

// create run_scan check file 
file_put_contents('run_scan', 1);

// script compare of advertisers (from Controller)
    set_time_limit(0);

    Log::init('TroubleshootingController');
    Log::start('heliumscanAction');

    $urlsModel = new Urls();
    $uData = $urlsModel->getCustomData(' name<>\'\'', array('name','site_id','param_url'));

    $found = array(); 

    //        $uData = array(
    //            1 => array('name'=>'How to tie a tie', 'site_id' => 14, 'param_url' => 'http://first.ua/priver/?G=asda'),
    //            2 => array('name'=>'Tips for buying shoes online', 'site_id' => 8, 'param_url' => 'http://second.ua/er/?G=asda')
    //        );

    Log::l('Root url: http://www.helium.com/',  Zend_Log::INFO);

    foreach ($uData as $aItem) { sleep(1); 

    //            $site_id = $aItem['site_id'];
        $param_url = $aItem['param_url'];
        $title = urlencode(strtolower($aItem['name']));
        $remoteUrl = 'http://www.helium.com/search/search?search_query='.$title;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remoteUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);            
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);               

        $heliumData = curl_exec($ch);            
        curl_close($ch);            

        // парсинг
        // делим результат на две части
        $classRes = false; 
        $classRes  = explode('class="results"', $heliumData);
        if(count($classRes) &&
            isset($classRes[1])) {
            // разбиваем по отдельным результатам поиска заключенным в тег <li>
            $liResults = explode('<li>', $classRes[1]);
            if(!empty($liResults)) {
                // убиваем первый результат так как содержит иформацию о том что мы ищем
                unset($liResults[0]);
                // перебираем все результаты что выбрасили нам на первую страницу 
                foreach ($liResults as $liItem) { 
                    // рассматриваем первую ячейку с li - $liItem именно первые результаты содержат максимальное совподение, которые мы и ищем 
                    preg_match_all("|<h1>(.*)</h1>|sUSi", $liItem, $h1Results);
                    if(isset($h1Results[1][0])) {
                        //ссылка на найденную статью
                        $resultLink = trim($h1Results[1][0]);
                        // название статьи
                        $articleTitle = strip_tags($resultLink); 
                        // сравнение результатов
                        $getTitle = trim(strtolower($articleTitle));
                        $localTitle = trim(strtolower($aItem['name'])); 
                        if($getTitle == $localTitle)  {
    //                                $sData =$sitesModel->getSites($site_id);
    //                                $minisite = $sData['domain'];
                            $found[] = array('link' => $resultLink, 'minisite'=>$param_url);
                            Log::l('Match is found [from: '.$param_url.']: '.$resultLink, Zend_Log::ERR);
                        }
                    }
                }
            }
        }
    }

    Log::end();
unlink('run_scan');
    echo (!empty($found)) ? 'Match is found: <br />' : '';
    echo '<pre>'; 
    print_r($found);
    die('Finished!');
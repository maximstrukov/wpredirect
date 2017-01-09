<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HandlerController
 *
 * @author dmitry
 */
class HandlerController extends BaseController {

    private $_typeOfBlackList = false;

    public function __construct( $controllerName = false ) {
        
        parent::__construct($controllerName);
    }
    
    function indexAction() 
    {   
        //test entry      
//        $_POST['user_ip'] = '127.0.0.1';
//        $_POST['wp_post_id'] = 1953;
//        $_POST['user_agent'] = 'USER AGENT FUCK_A_MACKA_FO';
//        $_POST['http_host'] = 'http://shopperify.loc';
        
        Log::init('HandlerController');
        Log::start('indexAction');
        
        Log::l('Received Post data:'.json_encode($_POST));
        
        if (isset($_POST['user_ip']) &&
                isset($_POST['wp_post_id']) &&
                isset($_POST['user_agent'])) {
            
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: application/json');

            //handler 
            $user_ip = ip2long(trim($_POST['user_ip']));
            $wp_post_id = App::escape(trim($_POST['wp_post_id']));
            $user_agent = App::escape(trim($_POST['user_agent']));
            
            // get site_id 
            $domain = WpAdmin::fetchFormattedDomain(App::escape(trim($_POST['http_host'])));
            $siteModel = new Site();
            Log::l('Try to get site data by term:  domain = '.$domain);
            $aDomain = $siteModel->getDataByFields(array('domain'=>$domain), false);
            //Log::l('Fetching data is: '.  json_encode($aDomain));
            
            $site_id = $aDomain['id'];
            // end get site_id 
            
            $urlsModel = new Urls(); 
            $whereFields = array('wp_post_id'=>$wp_post_id, 'site_id'=>$site_id); 
            Log::l('Try to get urls data by term: '.json_encode($whereFields));
            $advertiserData = $urlsModel->getDataByFields($whereFields, false);
            //Log::l('Fetching data is: '.  json_encode($advertiserData));
            
            // send email if get email_param
            if(isset($_POST['email_param'])) {
                
                $result = array();
                
                if($aDomain['email']) {
                    
                    $settingsModel = new Settings(); 
                    $system_email = $settingsModel->getSettingByKey('system_email');                     
                    
                    $to = $aDomain['email'];//'DimasXXX85@mail.ru';
                    $from = ($system_email) ? $system_email : 'wpredirects@system.com';
                    $subject = 'Information';

                    $referer = (strpos($_POST['referer'], 'http') || strpos($_POST['referer'], 'https')) ? $_POST['referer'] : 'http://'.$_POST['referer'];
                    $ipsName = $this->getUserISp($user_ip, $advertiserData['country']);
                    $timestamp = date('D, d M Y H:i:s');
                    
                    $body = 'User with IP "'.$_POST['user_ip'].'", '.PHP_EOL.
                            'from ISP "'.$ipsName.'", '.PHP_EOL.
                            'visited "'.$referer.'" '.PHP_EOL.
                            'in '.$timestamp;

                    // send 
                    $result = Mailer::sendHtmlMail($from, $to, $subject, $body);
                }
                
                echo json_encode($result);
                exit;              
            }
            // end send email if get email_param            
            
            $urls_id = $advertiserData['id'];
            $tracking_url = $advertiserData['exception_url'];
            
            // *** BLACKLISTS CHECKING ***
            
            $inBlackList = false;
            
            // checking use blacklists or not 
            if($aDomain['use_bl'] == 1) {
                
                // checking data to identify them in the "private_tu" black lists
                $inBlackList = $this->inPrivateUtBlacklist($tracking_url, $user_ip);

                // checking data to identify them in the "private" advertiser black list
                if(!$inBlackList)
                    $inBlackList = $this->inAdvertisersBlacklist($urls_id, $user_ip);

                // checking data to identify them in thr "private" site black list
                if(!$inBlackList)
                    $inBlackList = $this->inSiteBlacklist($site_id, $user_ip);

                // checking data to identify them in the "public" black lists
                if(!$inBlackList)
                    $inBlackList = $this->inPublicBlackList($user_ip);
            }

            if(!$inBlackList) {
                
                // checking has site "less_strict" option or not 
                $sData = $siteModel->getDataByFields(array('id'=>$site_id));
                $less_strict = ($sData['less_strict']) ? $sData['less_strict'] : false; 
                
                if($less_strict) {
                    
                    // write user data to statistic log at "less_strict" option
                    if (count($advertiserData)) {                
                        $this->setStatisticLog($advertiserData, $user_ip, $user_agent, 'exception'); 
                    }                    
                    
                    $result = array(
                        'exception_url' => $tracking_url
                    );                    
                }
                else {
                    
                    //checking has advertiser template or not
                    $advTempModel = new AdvertiserTemplate(); 
                    $atData = @$advTempModel->getDataByFields(array('urls_id'=>$urls_id));
                    $template_id = (isset($atData['template_id'])&&!empty($atData['template_id'])) ? $atData['template_id'] : false; 

                    if($template_id) {

                        // if advertiser has a temlate 
                        $templateModel = new Templates();
                        $tData = $templateModel->getDataByFields(array('id'=>$template_id));
                        Log::l('Advertiser "'.$advertiserData['name'].'" with id "'.$urls_id.'" has the "'.$tData['name'].'" EXCEPTION TEMPLATE!');

                        $result = $this->applyExceptionTemplate($template_id, $user_ip, $advertiserData, $user_agent);
                    }
                    else {

                        // general internal chekinhg
                        if (count($advertiserData)) {
                            $url = $this->redictor($advertiserData, $user_ip, $user_agent);
                            $result = array(
                                'exception_url' => $url
                            );
                        } else
                            $result = array(
                                'exception_url' => false
                            );
                    }
                }
            } 
            else {
                
                Log::l('User with ip "'.$_POST['user_ip'].'" exists in the "'.$this->_typeOfBlackList.'" BLACKLIST!');
                
                // write user data to statistic log 
                if (count($advertiserData)) {                
                    $this->setStatisticLog($advertiserData, $user_ip, $user_agent, 'redirect'); 
                }
                
                $result = array(
                    'exception_url' => false
                );                
            }
            
            Log::l('Result (response to wp-minisite):'.json_encode($result));
                    
            echo json_encode($result);
            Log::end();
            exit;
        }
        else {
            
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header("HTTP/1.0 404 Not Found");
            echo 'Only with special Post data.';
        }
        
        Log::end();
    }
    
    /**
     * use Template for advertiser if that exist for it 
     * @param int $template_id
     * @param mixed $user_ip
     * @param array $advertiserData 
     * @param string $user_agent
     * @return array - with excepotion_url
     */
    private function applyExceptionTemplate($template_id, $user_ip, $advertiserData, $user_agent)
    {
        $type = 'redirect';
        $redir_url = false;                             
        
        if(!empty($user_ip)) {
            
            $user_ip = (!is_string($user_ip)) ? $user_ip : ip2long(trim($user_ip));
            
            // get isp_data by template_id 
            $tempIspModel = new TemplateIsp();
            $tiData = $tempIspModel->getDataByFields(array('template_id'=>$template_id)); 
            $isp_data_id = $tiData['isp_data_id']; 
            $ispDataModel = new IspData(); 
            $idData = $ispDataModel->getDataByFields(array('id'=>$isp_data_id));

            $country = $idData['country'];
            $ipsData = unserialize($idData['isp_data']);
            
            $userIspName = $this->getUserISp($user_ip, $country);
            
            if(in_array($userIspName, $ipsData)) {
                
                $type = 'exception';
                $redir_url = $array['exception_url'];                
            }
            
            //write statistic 
            $this->setStatisticLog($advertiserData, $user_ip, $user_agent, $type);
        }
        
        return $result = array(
            'exception_url' => $redir_url
        );        
    }
    
    /**
     * get user isp name
     * @param int $ip
     * @param chat $country_code
     * @return isp name by ip
     */    
    private function getUserISp($ip, $country_code = false)
    {         
        $uitModel = new UrlsIptable(); 
        $res = $uitModel->getUserISpByIP($ip, $country_code);
        
        return $res ? $res['isp_name'] : false;
    }

    /**
     * @param int $ip
     * @param chat $country_code
     * @param int $url_id
     */
    private function isIPinProvidersIPs($ip, $country_code, $url_id) 
    {
        $sql = 'SELECT count(*) as cnt FROM urls_providers
                INNER JOIN urls_iptable on urls_providers.provider_name	= urls_iptable.isp_name /*and country_code = :country*/
                where `urls_id` = :url_id and :ip between start_ip and end_ip';
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(':url_id' => $url_id, ':country' => $country_code, ':ip' => $ip));
        $res = $smtm->fetch();

        return $res ? $res[0] : 0;
    }

    /**
     * @param array
     *    id 
     *    name
     *    redirect_url
     *    exception_url 
     *    ips  
     *    country
     *    start   
     *    end      
     *    enter_url  
     * @param char $cur_ip
     * @param char $user_agent 
     */
    private function redictor($array, $cur_ip, $user_agent) 
    {
        $exception_time = false;
        $exception_ips = false;

        $start = (int) strtotime($array['start']);
        $end = (int) strtotime($array['end']);

        $now = (int) time();

        if ($now >= $start && $now < $end)
            $exception_time = true;

        $tmp_ips = preg_replace('/\\s+-\\s+/s', '=', $array['ips']);
        $ips = preg_split("/[\s,;]+/", trim($tmp_ips));

        if ($this->isIPinProvidersIPs($cur_ip, $array['country'], $array['id']) > 0) {
            $exception_ips = true;
        } else {
            foreach ($ips as $ip) {
                $interval = explode('=', $ip); //patch *.*.*.* - *.*.*.*
                if (count($interval) > 1) {
                    $start_ip = ip2long(str_replace('*', 0, trim($interval[0])));
                    $end_ip = ip2long(str_replace('*', 255, trim($interval[1])));
                } else {
                    $start_ip = ip2long(str_replace('*', 0, $ip));
                    $end_ip = ip2long(str_replace('*', 255, $ip));
                }

                if (!$start_ip || !$end_ip)
                    continue;

                if ($cur_ip >= $start_ip && $cur_ip <= $end_ip) {
                    $exception_ips = true;
                    break;
                }
            }
        }

        $redir_url = false;

        $type = 'redirect';

        if ($exception_time || $exception_ips) {
            $type = 'exception';
            $redir_url = $array['exception_url'];
        }
        
        $this->setStatisticLog($array, $cur_ip, $user_agent, $type);
        
        return $redir_url;
    }
    
    private function checkUserIpInBlacklist($user_ip, $ips_data)
    { 
        $rIP = long2ip($user_ip);
        // try to implement fast search
        if(strpos($ips_data, $rIP)>0 || strpos($ips_data, $rIP) === 0) {
            return true; 
        }
        
        // check more detailed or not 
        $is_dash = strpos($ips_data, '-') ? true : false; 
        $is_asterisk = strpos($ips_data, '*') ? true : false;
        
        if($is_dash || $is_asterisk) {
            // detailed checking 
            $ips_arr = explode(PHP_EOL, $ips_data);

            foreach($ips_arr as $ips) {

                // if range like a 127.0.0.1-255
                if(strpos($ips, '-')) {
                    // is range 
                    $rangeData = explode('-' , $ips); 
                    $minVal = @ip2long(trim($rangeData[0]));
                    $maxVal = @ip2long(trim($rangeData[1]));

                    if(intval($maxVal) && intval($minVal))
                        if(($minVal<=$user_ip) && ($user_ip <= $maxVal))
                            return true; 
                }
                // if range like a 127.0.*.*
                else if (strpos($ips, '*')) {

                    $cPoint = strpos($ips, '*'); 
                    $curCutIp = trim(substr($rIP, 0, $cPoint)); 
                    $getCutIp = trim(substr($ips, 0, $cPoint));

                    if($curCutIp == $getCutIp) 
                        return true; 
                }
                // if simple ip, for example: 127.0.0.1
                else {
                    // is not range 
                    $blackIp = @ip2long(trim($ips));
                    if(intval($blackIp)) 
                        if($blackIp == $user_ip)
                            return true; 
                }
            }
        }
        
        return false; 
    } 
    
    private function checkTUInBlacklist($tracking_url, $tu_data)
    { 
        
        $tu_arr = explode(',', $tu_data);
        
        foreach ($tu_arr as $tuItem) {
            
            $uData = parse_url(trim($tuItem)); 
            $host = isset($uData['host']) ? $uData['host'] : (isset($uData['path']) ? $uData['path'] : false);
            if($host)
                if(strpos($tracking_url, $host))
                    return true; 
        }
        
        return false; 
    }     
    
    /**
     * inPublicBlackList
     * @param mixed $user_ip : @ip2long from user_id or ip in the standart view 
     * @return boolean : if ip isset in the blacklist - true, else returned false;  
     */
    private function inPublicBlackList($user_ip) 
    {
        if(!empty($user_ip)) {
            
            $user_ip = (!is_string($user_ip)) ? $user_ip : ip2long(trim($user_ip));
            
            $blacklistsModel = new Blacklists(); 
            $blacklistsData = $blacklistsModel->getDataByFields(array('type'=>'public'),true);

            foreach($blacklistsData as $blacklist) {

                $ips_data = $blacklist['ips_data'];
                if(!empty($ips_data)) {
                    if($this->checkUserIpInBlacklist($user_ip, $ips_data)) {

                        $this->_typeOfBlackList = 'in public';
                        return true; 
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * inAdvertisersBlacklist 
     * @desc check existing ip and advertiser tracking url in PrivateUT blacklists
     * @param string $tracking_url
     * @param mixed $user_ip
     * @return boolean 
     */    
    private function inPrivateUtBlacklist($tracking_url, $user_ip)
    {
        if(!empty($user_ip)) {        
            
            $user_ip = (!is_string($user_ip)) ? $user_ip : ip2long(trim($user_ip));
            
            // get all privat ut blacklists 
            $blacklistsModel = new Blacklists(); 
            $bData = $blacklistsModel->getDataByFields(array('type'=>'private_tu'), true); 
            
            foreach ($bData as $privateTUbs) {
                
                // check exist advertiser tracking url in tu_data
                $tu_data = $privateTUbs['tu_data'];
                $inTUData = ($this->checkTUInBlacklist($tracking_url, $tu_data)) ? true : false;
                
                $inIpsData = false; 
                
                if($inTUData) {
                    // check exist user_ip in ips list
                    $ips_data = $privateTUbs['ips_data'];
                    $inIpsData = ($this->checkUserIpInBlacklist($user_ip, $ips_data)) ? true : false;
                }
                
                if($inIpsData && $inTUData) {
                    
                    $this->_typeOfBlackList = 'in private W/TU';
                    return true;
                }   
            }
        }
        
        return false;         
    }

    /**
     * inAdvertisersBlacklist 
     * @desc check existing ip in advertiser's blacklist
     * @param int $urls_id
     * @param mixed $user_ip
     * @return boolean 
     */
    private function inAdvertisersBlacklist($urls_id, $user_ip)
    {
        if(!empty($user_ip)) {
            
            $user_ip = (!is_string($user_ip)) ? $user_ip : ip2long(trim($user_ip));        
            
            // check blacklist for that advertiser (get blacklist_id)
            $abModel = new AdvertiserBlacklist();
            $abData = $abModel->getDataByFields(array('urls_id'=>$urls_id)); 
            $blacklist_id = ($abData['blacklist_id']) ? $abData['blacklist_id'] : false;

            //get blacklist data by blacklist_id
            if($blacklist_id) {

                $blacklistsModel = new Blacklists(); 
                $blacklistsData = $blacklistsModel->getDataByFields(array('id'=>$blacklist_id));        

                // check user ip in the that balcklist
                $ips_data = $blacklistsData['ips_data'];
                if($this->checkUserIpInBlacklist($user_ip, $ips_data)) {
                    
                    $this->_typeOfBlackList = 'in private advertiser';
                    return true; 
                }
            }
        }
        
        return false; 
    }
    
    /**
     * inSiteBlacklist
     * @desc check existing ip in the site blacklist
     * @param int $site_id
     * @param mixed $user_ip
     * @return boolean
     */
    public function inSiteBlacklist($site_id, $user_ip)
    {
        if(!empty($user_ip)) {
            
            $user_ip = (!is_string($user_ip)) ? $user_ip : ip2long(trim($user_ip));
            
            // check blacklist for this site (get blacklist_id)
            $sbModel = new SiteBlacklist(); 
            $sbData = $sbModel->getDataByFields(array('site_id'=>$site_id));
            $blacklist_id = ($sbData['blacklist_id']) ? $sbData['blacklist_id'] : false;

            //get blacklist data by blacklist_id
            if($blacklist_id) {

                $blacklistsModel = new Blacklists(); 
                $blacklistsData = $blacklistsModel->getDataByFields(array('id'=>$blacklist_id));        

                // check user ip in the that balcklist
                $ips_data = $blacklistsData['ips_data'];
                if($this->checkUserIpInBlacklist($user_ip, $ips_data)) {
                    
                    $this->_typeOfBlackList = 'in private site';
                    return true;
                }
            }
        }
        
        return false;
    }    
    
    private function setStatisticLog($advData, $cur_ip, $user_agent, $type) 
    {
        $uitModel = new UrlsIptable(); 
        $res = $uitModel->getUserISpByIP($cur_ip, $advData['country']);
        
        $sql = "INSERT INTO `urls_logs`
                    (`added`,`added_date`,`url_id`,`type`, `remote_ip`, `user_agent`, `isp_name`, `country_code`, `start_ip`, `end_ip`)
                    VALUES
                    (now(), date(now()), :url_id, :type, :remote_ip, :user_agent, :isp_name, :country_code, :start_ip, :end_ip)
                ";
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute(array(
            ':url_id' => $advData['id'],
            ':type' => $type,
            ':remote_ip' => $cur_ip,
            ':user_agent' => $user_agent,
            ':isp_name' => $res['isp_name'],
            ':country_code' => $res['country_code'],
            ':start_ip' => $res['start_ip'],
            ':end_ip' => $res['end_ip']
        ));  
        
        return true; 
    }
    
    /**
     * Site checker
     */
    
    private $_siteChecker = 'http://sitecheck.sucuri.net'; 
    private $_positive = 'Verified Clean'; 

    public function sitecheckerAction() 
    {
        Log::init('SiteChecker');
        Log::start('sitechecker');
        
        set_time_limit(0);
        
        $siteModel = new Site();
        $sData = $siteModel->getDataByFields(array(), true);
        
        foreach($sData as $sItem) { 
        
            $domain = $sItem['domain'];
            
            $cUrl = $this->_siteChecker.'/results/'.$domain;
            
            Log::l('Request : '.$cUrl);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Accept-Language :en-us,en;q=0.5'
                ));
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1');
            curl_setopt($ch, CURLOPT_URL, $cUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
            $response = curl_exec($ch);
            $response = (string)$response;
            curl_close($ch);  
            
            // get site status
            $status = substr($response, strpos($response,'status:'), strlen($response));
            $status = substr($status, 0, strpos($status, '</span></td>'));
            $verification = str_replace('status:', '', $status);
            $verification = trim(strip_tags($verification));
            
            Log::l('Received VERIFICATION: "'.$verification.'"');
            
            // get warning
            $warning = false; 
            $w_start = (strpos($response,'<td>warn:')>0) ? strpos($response,'<td>warn:') : false; 
            if($w_start) {
                $warn = substr($response, $w_start, strlen($response));
                $warn = substr($warn, 0, strpos($warn, '</span></td>'));            
                $warning = str_replace('warn:', '', $warn);
                $warning = trim(strip_tags($warning));
            }
            if($warning) Log::l('Received WARNING: "'.$warning.'"');
            
            $status = (($verification == $this->_positive) && empty($warning)) ? true : false;
            
            Log::l('Set status: ['.(($status) ? 1 : 0).'] (Notice: 1-OK, 0-fail)');
            
            // set status in the "Site" db table:
            $siteModel->updateData(array('status'=>$status), array('id'=>$sItem['id']));
        }
        
        return true;
    }
    
    /**
     * Curl checker 
     * @desc check work of CURL by 80 port from remote mini sites
     */
    
    public function curlcheckerAction() 
    {
        Log::init('CurlChecker');
        Log::start('curlchecker');
        
        set_time_limit(0);
        
        $siteModel = new Site();
        $sData = $siteModel->getDataByFields(array(), true);
        
        $result = false;
        
        foreach($sData as $sItem) {
            
            Log::l('Check: http://'.$sItem['domain']); sleep(1);
            
            $site_id = $sItem['id'];

            $apiModel = new RedirectAPI($site_id);
            $result = $apiModel->checkRemoteCurl();
            
            $curl_status = ($result === '1')? true : false;
            
            if($curl_status) { 
                
                // set like a fixed site 
                $siteModel->updateData(array('curl_status'=>$curl_status), array('id'=>$site_id));
                
                Log::l('Result: OK;');
            } 
            else {
                Log::l('Result: FAILED;');
                $result = str_replace(PHP_EOL, '', $result);
                $result = substr($result, 0, 1536);
                Log::l('Error detail (response): '.$result);
                
                if(!empty($result)) {
                    
                    // set like a broken site 
                    $siteModel->updateData(array('curl_status'=>$curl_status), array('id'=>$site_id));
                    
                    // sending details by email 
                    $settingsModel = new Settings(); 
                    $system_email = $settingsModel->getSettingByKey('system_email'); 
                    $admin_email = $settingsModel->getSettingByKey('admin_email'); 
                    $from = ($system_email) ? $system_email : 'wpredirects@system.com'; 
                    $to = $admin_email;
                    $subject = 'CURL PROBLEM';
                    $body = 'http://'. $sItem['domain'].' has CURL problems (no outgoing connections allowable from them).'.PHP_EOL.
                            'Please go to Sites tab and investigate.'.PHP_EOL.
                            'Log data is: '.PHP_EOL.
                            'Error detail (response): '.$result;
                    Mailer::sendHtmlMail($from, $to, $subject, $body); 
                }
            }
        }
        
        print('Curlchecker completed.');
        return true;
    }
    
    /**
     * changeadminloginAction
     * @desc automatically changed login in the remote sites and in the site settings for wpredirect tool 
     */
    public function changeadminloginAction()
    {
        $siteModel = new Site(); 
        $sData = $siteModel->getSites();
        
        foreach ($sData as $siteItem) { sleep(1);
            
            $site_id = $siteItem['id'];
            $old_login = $siteItem['wp_login'];  
            $domain = $siteItem['domain']; 
            
            $partDomain = explode('/',$domain); 
            
            $wpname = ''; 
            if(isset($partDomain[0])) {
                $urlPart = explode('.',$partDomain[0]);
                $wpname = (isset($urlPart[0])) ? $urlPart[0].'_' : '';
            }
            
            $lang = (isset($partDomain[1])) ? $partDomain[1].'_' : '';
            
            //minisitename_lang_admin
            $new_login = $wpname.$lang.'admin';
            
            // changed admin login in remote site
            $apiModel = new RedirectAPI($site_id);
            $result = $apiModel->changeaAdminLogin($old_login, $new_login);
            
            if($result) {     
                
                $siteModel->updateData(array('wp_login'=>$new_login), array('id'=>$site_id));
            } 
        }
        
        print('Changeadminlogin completed.');
        return true;        
    }
}
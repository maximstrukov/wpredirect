<?php

/**
 * @param int $ip
 * @param chat $country_code
 * @param int $url_id
 */
function isIPinProvidersIPs($ip, $country_code, $url_id)
{
    $sql = 'SELECT count(*) as cnt FROM urls_providers
            INNER JOIN urls_iptable on urls_providers.provider_name	= urls_iptable.isp_name and country_code = "'.$country_code.'"
            where `urls_id` = '.$url_id.' and '.$ip.' between start_ip and end_ip';

    $result = mysql_query($sql);

    $res = mysql_fetch_row($result);

    return $res[0];
}

function redictor($array)
{
/*
 id 
 name
redirect_url
exception_url 
ips  
 country
 start   
 end      
 enter_url  
*/
    
//echo "<pre>";
//print_r($array);
//die();
    
	$exception_time = false;
	$exception_ips = false;

	$start =(int) strtotime($array['start']);
	$end = (int) strtotime($array['end']);

    $now = (int) time();

	if($now>=$start && $now<$end) $exception_time = true;


    $tmp_ips =  preg_replace('/\\s+-\\s+/s', '=', $array['ips']);

    $ips = preg_split("/[\s,;]+/", trim($tmp_ips));

    $cur_ip = ip2long($_SERVER['REMOTE_ADDR']);
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if(isIPinProvidersIPs($cur_ip, $array['country'],$array['id'])>0){
        $exception_ips = true;
    }else{
        foreach($ips as $ip)
        {

            $interval = explode('=', $ip); //patch *.*.*.* - *.*.*.*
            if(count($interval)>1) {
                $start_ip = ip2long(str_replace('*', 0, trim($interval[0])));
                $end_ip = ip2long(str_replace('*', 255, trim($interval[1])));
            }
            else {
                $start_ip = ip2long(str_replace('*', 0, $ip));
                $end_ip = ip2long(str_replace('*', 255, $ip));
            }

            if(!$start_ip || !$end_ip) continue;

            if($cur_ip>=$start_ip&&$cur_ip<=$end_ip) {$exception_ips = true; break;}
        }
    }
    
    $redir_url = $array['redirect_url'];
    $type = 'redirect';
	if($exception_time || $exception_ips)
    {
        
        $type = 'exception';
		$redir_url = $array['exception_url'];
    }
    $sql = "INSERT INTO `urls_logs`
                (`added`,`added_date`,`url_id`,`type`, `remote_ip`, `user_agent`)
                VALUES
                (now(), date(now()), ".$array['id'].",'".$type."', ".$cur_ip.", '".$user_agent."')
            ";
    mysql_query($sql);
    
	return $redir_url;	
}
//date_default_timezone_set('GMT');

$url = redictor($current_url);

//echo 'URL: '.'http://'.$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']. "<br />";
//echo 'REMOTE IP: '.$_SERVER['REMOTE_ADDR']. "<br />";
//echo 'TIME: '.date('h:i'). "<br />";

//die($url);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><body bgcolor="white"><a id="test" style="color:white" href="' . $url . '">test2</a><script type="text/javascript">var t=document.getElementById("test"); if (navigator.appName=="Netscape") location.href=t.href; else t.click();</script></body></html>';
die();

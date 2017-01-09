<?php
die('access denied');
//echo "<pre>";
//print_r($_SERVER);
//die();

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once 'config.php';

mysql_connect($host, $user, $pass);
mysql_select_db($db_name);


session_start();

$base_dir = "/redir/";
$cur_url = str_replace( $base_dir, '', $_SERVER['REQUEST_URI']);

$cur_url = 'http://'.$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

//die($cur_url);



if(strpos($cur_url, 'http://'.$_SERVER['SERVER_NAME'] . $base_dir . 'index.php')===0){
    $act = (isset($_REQUEST['act'])) ? trim($_REQUEST['act']) : 'index';

    if (!empty($act) && function_exists($act.'Action')) {
        call_user_func($act.'Action');
    }
}
else {
    $sql = 'SELECT * FROM urls where enter_url = "'.mysql_real_escape_string(trim($cur_url)).'" limit 1';
    
    $result = mysql_query($sql);    
    $current_url = mysql_fetch_assoc($result);
    
    if($current_url)
        require_once 'redirector.php';
    else {
        header("HTTP/1.0 404 Not Found");
        die(' 404 Not Found');
    }
}

function indexAction()
{
    $time_options = array();
    
    for($h=0;$h<24;$h++)
    {
        for($m=0;$m<60;$m+=20)
            $time_options[] = ($h<10?'0':'').$h.':'.($m<10?'0':'').$m;
    }
    
    require_once dirname(__FILE__) . '/views/index.php';
}

function tableAction()
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');    
    
    $sql = 'SELECT * FROM urls ORDER By name';
    
    $result = mysql_query($sql);
    
    $resArr = array();
    
    while ($row = mysql_fetch_assoc($result)) {
        $rRow[0] = $row['name'];
        $rRow[1] = $row['ips'];
        
        $start = date('H:i',strtotime($row['start']));
        $end = date('H:i',strtotime($row['end']));

        if($start!==$end)
        {
            $rRow[2] = $start.'-'.$end;
        }
        else
            $rRow[2] = '';
        
        $rRow[3] = '<a href="#" url_id="'.$row['id'].'" class="edit" onclick="open_edit(this); return false;">edit</a> <a href="#" url_id="'.$row['id'].'" class="delete" onclick="open_delete(this); return false;">delete</a>';
        
        $resArr[] = $rRow;
    }
    
    $response = array('aaData'=>$resArr);
    
    echo json_encode($response);
    exit();
}

function saveAction()
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');      
    
    if($_POST)
    {
        $errors = validate($_POST);
        
        if(count($errors)){
            echo json_encode(array('errors'=>$errors));
            exit();
        }
        
        if(intval($_POST['id'])) {
            $sql = 'UPDATE `urls`
                        SET
                        `name` = "'.mysql_real_escape_string(trim($_POST['name'])).'",
                        `redirect_url` = "'.mysql_real_escape_string(trim($_POST['redirect_url'])).'",
                        `exception_url` = "'.mysql_real_escape_string(trim($_POST['exception_url'])).'",
                        `ips` = "'.mysql_real_escape_string(trim(@$_POST['ips'])).'",
                        `country` = "'.mysql_real_escape_string(trim(@$_POST['country'])).'",
                        `start` = "'.mysql_real_escape_string(trim($_POST['start'])).'",
                        `end` = "'.mysql_real_escape_string(trim($_POST['end'])).'",
                        `enter_url` = "'.mysql_real_escape_string(trim($_POST['enter_url'])).'"
                        WHERE  `id` = '.intval($_POST['id']).'

                    ';
        }
        else {
            $sql = 'INSERT INTO `urls`
                        (
                        `name`,
                        `redirect_url`,
                        `exception_url`,
                        `ips`,
                        `country`,
                        `start`,
                        `end`,
                        `enter_url`)
                        VALUES
                        (

                        "'.mysql_real_escape_string(trim($_POST['name'])).'",
                        "'.mysql_real_escape_string(trim($_POST['redirect_url'])).'",
                        "'.mysql_real_escape_string(trim($_POST['exception_url'])).'",
                        "'.mysql_real_escape_string(trim(@$_POST['ips'])).'",
                        "'.mysql_real_escape_string(trim(@$_POST['country'])).'",
                        "'.mysql_real_escape_string(trim($_POST['start'])).'",
                        "'.mysql_real_escape_string(trim($_POST['end'])).'",
                        "'.mysql_real_escape_string(trim($_POST['enter_url'])).'"
                        )
                        ';
            
            
        }
        mysql_query($sql);        
    }
    
    echo "saved";
    exit();
}

function isValidURL($url)
{
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function validate($post)
{
    $errors = array();
    if($post['name']=='') $errors['name'] = 'Is not valid';
    if(!isValidURL($post['redirect_url'])) $errors['redirect_url'] = 'Is not valid';
    if(!isValidURL($post['exception_url'])) $errors['exception_url'] = 'Is not valid';
    if(!isValidURL($post['enter_url'])) $errors['enter_url'] = 'Is not valid';
    
    if(!preg_match('/\d\d:\d\d/i', $post['start'])) $errors['start'] = 'Wrong format';
    if(!preg_match('/\d\d:\d\d/i', $post['end'])) $errors['end'] = 'Wrong format';
    
    $start = date('H:i',strtotime($post['start']));
    $end = date('H:i',strtotime($post['end']));
    
    if($start > $end) $errors['start'] = 'Start couldn\'t be later than end';
    
    
    if(trim($post['ips'])) {
        $ips = preg_split("/[\s,;]+/", $post['ips']);

        foreach($ips as $ip)
        {
            $test_ip = str_replace('*', 255, $ip);

            if(ip2long($test_ip)===false){
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
    if(isset($_REQUEST['id'])&&intval($_REQUEST['id'])>0)
    {
        $sql = 'SELECT * FROM urls where id='.intval($_REQUEST['id']);
    
        $result = mysql_query($sql);    
        
        $response = mysql_fetch_assoc($result);
        
        $response['start'] = date('H:i',strtotime($response['start']));
        $response['end'] = date('H:i',strtotime($response['end']));
    }
    else{
       $response['id'] = '';
       $response['name'] = '';
       $response['redirect_url'] = '';
       $response['exception_url'] = '';
       $response['country'] = '';
       $response['start'] = '00:00';
       $response['end'] = '00:00';
       $response['enter_url'] = 'http://'.$_SERVER['SERVER_NAME'] . $base_dir. md5(mktime());
       
    }
    echo json_encode($response);
    exit();
}

function deleteAction()
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');   
    if(isset($_REQUEST['id']))
    {
        $sql = 'DELETE FROM urls where id='.intval($_REQUEST['id']).' LIMIT 1';
    
        $result = mysql_query($sql);    
        
    }
    echo json_encode('ok');
    exit();
}

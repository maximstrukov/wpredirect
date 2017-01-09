<?php

define('ROOT_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

$cur_url = 'http://console.access';
if(isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])) {
    $cur_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
}

if ($cur_url == 'http://console.access') {
    $path = '/wpredirects/admin/';
    define('ABS_URL', $cur_url.$path);
} else {
    $aParts = parse_url($cur_url);
    $path = str_replace('index.php', '', $aParts['path']);
    define('ABS_URL', 'http://'.$aParts['host']. $path);
}
define('BASE_URL', $path);

require_once ROOT_DIR.'../config.php';
require_once ROOT_DIR.'includes/autoload.php';

//mysql_connect($host, $user, $pass);
//mysql_select_db($db_name);

try {
    # MySQL with PDO_MYSQL  
    App::inst()->db = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);  
//    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );  
//    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );  
    App::inst()->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e) {  
    echo $e->getMessage();
    exit;
} 

session_start();

//$cur_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
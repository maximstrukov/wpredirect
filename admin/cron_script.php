<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '../config.php';

mysql_connect($host, $user, $pass);
mysql_select_db($db_name);

// get files 
$dh = opendir('logs/');
while($filename = readdir($dh)) {
    
    $year = (string)date('Y');
    if(strpos($filename, $year)===0) {
        //delete logs files
        @unlink('logs/'.$filename);
    }
}

// clear logs data that were write 30 days ago 
$sql = "DELETE FROM urls_logs where added<DATE_SUB(now(), INTERVAL 30 DAY) and id>0";
mysql_query($sql);

// clear empty data from advertisers (urls table)
$sql = "DELETE FROM urls WHERE name IS NULL";
mysql_query($sql);


<?php

$apptitle = "timedesk";


error_reporting(E_ALL | E_STRICT);

//HOST SPECIFIC
define("mysql",1);
define("sqlite2",0);
define("USERS",0);
define("NICE_URLS",1); //apache mod_rewrite enabled



define("MYSQL_HOST","localhost");
define("MYSQL_USER","root");
define("MYSQL_PASS","");
define("MYSQL_DB","timedesk");


define("SQLITE2_DB","sqlite2.db");
define("FOLDER_SEPARATOR","/");
define("HTTP_POST_DOUBLE_SLASH","1"); //used in HTTPPostValue, HTTPPostFix


$base_url = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/";
if(substr($base_url,strlen($base_url)-2,2)=='//') {
	$base_url = substr($base_url,0,strlen($base_url)-1);
}
date_default_timezone_set("Asia/Almaty");

?>
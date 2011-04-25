<?php
/************************* MAIN ****************************/
session_start();

require_once "app.php";
require_once "bios.php"; //all functions
require_once "conf.php";


if(function_exists("data_model")) 
  data_model();

db_connect();
                    
error_reporting(E_ALL | E_STRICT);

if(!isset($_SESSION['lang'])) {
	$_SESSION['lang'] = 'eng';
}

global $submenu;
global $pageheader;
global $log;
global $content;
$query = form_post('q');
$submenu = "";
$log = "";
$content = "";
$pageheader = "";

//OTHER MODULES
$modules = array();
foreach(glob("uses/*.php") as $module) { 
	require_once $module; 
	$module_name = str_replace("uses/","",str_replace(".php","",$module)); 
	$modules[] = $module_name;
	$fname = $module_name."_connect";
	if(function_exists($fname)) {
		$fname();
	}	
}


if(form_post("l")) {
  if(form_post("l")=="rus") {
	  $_SESSION['lang'] = "rus";
  } else {
	  $_SESSION['lang'] = "eng";
  }
  redir(form_post('q'));
}


if($_SESSION['lang']=='rus') {
	$rus_highlight = "#C0BEBF";
	$eng_highlight = "#515151";
} else {
	$rus_highlight = "#515151";
	$eng_highlight = "#C0BEBF";
}

//LOGIN - different menus generation for different user's

$menu_logout = "";
$menu_user = "";

$menu_users = "";
if(user_authorized() && $_SESSION['userid']==1) 
   $menu_users = ":: <a href=?q=users>Users</a>";


if(!isset($_GET['q'])) {
   if(function_exists("def_q")) {
      $_GET['q'] = def_q();
   } else {
      $_GET['q'] = 'home';
   }
}


$parts = explode('/',$_GET['q']);


//check for pages/sometext.txt file
$pagename = $_GET['q'];
$pagename = str_replace(".","",$pagename);

$filename = "pages/$pagename".".txt";
if(file_exists($filename)) {
    $file = file_get_contents($filename);
    $file = str_replace("\r","<br>",$file);


    preg_match_all("|{![^}]*}|",$file,$matches);
    foreach($matches[0] as $value) {
        $varname = substr($value,2,strlen($value)-3);
        if(file_exists($varname)) {
          $file_content = file_get_contents($varname);
          
        }
        $file = str_replace("{!$varname}",$file_content,$file);
    }
    $content .= $file;
}


//CHECK FOR page_function
$function = "page";
$appropriate_function = "";
$appropriate_index = -1;
$temp_template = "";
$def_template = "";
foreach($parts as $i=>$part) {
    if($temp_template) { $temp_template .= "_";}
    $temp_template .= "$part";
    $function .= "_$part";
    if(function_exists($function)) {
        $appropriate_function = $function;
        $appropriate_index = $i;
	$def_template = $temp_template;
    }
}

//CHECK FOR before_function
$function = "before";
$before_function = "";
foreach($parts as $i=>$part) {
    $function .= "_$part";
    if(function_exists($function)) {
        $before_function = $function;
    }
}

if($before_function) {
  $before_function();	
}

//CHECK FOR function_page
$function = "";
foreach($parts as $i=>$part) {
   if($function!="")
     $function .= "_";
   $function .= "$part";
   if(function_exists($function."_page") && $appropriate_index<$i) {
      $appropriate_function = $function."_page";
      $appropriate_index = $i;
   }

   if(function_exists($function."_pg") && $appropriate_index<$i) {
      $appropriate_function = $function."_pg";
      $appropriate_index = $i;
   }
   
}

//// self_q
$self_q = "";
for($i=0;$i<=$appropriate_index;$i++) {
  if($self_q) $self_q .= "/";
  $self_q .= $parts[$i];
}

//ARGUMENTS
$args = array();
for($i=$appropriate_index;$i<count($parts)-$appropriate_index+1;$i++) {
   if(isset($parts[$i+1]))
   $args[] = $parts[$i+1];
}

//evaluate content
if($appropriate_function) {

    $i = $appropriate_index;
    switch(count($parts)-$appropriate_index-1) {
        case 0: $content = $appropriate_function(); break;
        case 1: $content = $appropriate_function($parts[$i+1]); break;
        case 2: $content = $appropriate_function($parts[$i+1],$parts[$i+2]); break;
        case 3: $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3]); break;
        case 4: $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3],$parts[$i+4]); break;
        case 5: $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3],$parts[$i+4],$parts[$i+5]); break;
        case 6: $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3],$parts[$i+4],$parts[$i+5],$parts[$i+6]); break;
        case 7: $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3],$parts[$i+4],$parts[$i+5],$parts[$i+6],$parts[$i+7]); break;
        default: log_message("Too many parameters"); $content = $appropriate_function($parts[$i+1],$parts[$i+2],$parts[$i+3]); break;
    }

}

$content = script_uses_head().$content;
if(function_exists("before_content_post")) {
  before_content_post($content);
}

$pagename = $_GET['q'];
$pagename = str_replace(".","",$pagename);
if(!$content && !$appropriate_function) {
    $content = "<h1>ERROR:<br> Can't render '".$_GET['q']."'</h1>";
}

if(!isset($template)) {
  $template = file_get_contents("main.html");
}

if(function_exists("before_template_parse")) {
	before_template_parse($template);
}

/// SQP TEMPLATE ENGINE
replace_files($template); // {!something.js} {!something.css}
replace_my_tags($template); // {href {f {call
replace_globals($template); // {!global} {!global}
translate_parse($template); // {~rus} {~eng}

echo $template;

//don't show mysql not freed etc
ini_set("display_errors",0);
ini_set("mysql.trace_mode",0);

?>
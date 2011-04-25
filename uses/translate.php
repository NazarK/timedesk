<?php
define("DEFAULT_LANG","orig");

function set_lang($value) {
  global $apptitle;
  $_SESSION[$apptitle.'lang'] = $value;
}

function page_lang($value) {
	global $apptitle;
    $_SESSION[$apptitle.'lang'] = $value;
    if(isset($_SERVER['HTTP_REFERER'])) {
      Header("Location: ".$_SERVER['HTTP_REFERER']);
    } else die();    
}

function page_rus() {
  return page_lang("rus");
}

function page_eng() {
  return page_lang("eng");
}


function lang() {
  global $apptitle;
  if(!isset($_SESSION[$apptitle.'lang'])) return DEFAULT_LANG;
  return $_SESSION[$apptitle.'lang'];
}

function eng() {
  return lang()=='eng';
}

function rus() {
  return lang()=='rus';
}

$dictionary = array();
function trans($s) {
	if(lang()==DEFAULT_LANG) return $s;
	global $dictionary;
	if(count($dictionary)==0) {
		$lines = file("dictionary.txt");
		foreach($lines as $line) {
		  $parts = explode('=',$line);
		  $dictionary[$parts[0]] = trim($parts[1]);
		}
	}
	if(!isset($dictionary[$s])) return $s;
	return $dictionary[$s];

}

function _T($s) {
  return trans($s);
}


function fld_trans($s) {
  $parts = explode("||",$s);
  if(count($parts)==1)  {
	  $parts = explode("english=",$s);
  }

  if(count($parts)==1)  {
	  $parts = explode("inenglish:",$s);
  }
  if(eng()) {
	if(isset($parts[1])) return $parts[1];
	else return $parts[0]; 
  } else {
	return $parts[0];
  }
}

function obj_trans($o) {
  foreach($o as $name=>$value) {
	  $o->$name = fld_trans($o->$name);
  }
  return $o;
}


?>
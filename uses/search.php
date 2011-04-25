<?php


function page_search_prepare() {
	requires_admin();
	echo "updating content_search value<Br>";
    $rr = db_fetch_objects(db_query("SELECT * FROM pages"));
	foreach($rr as $r) {
	  echo "page p/$r->id ".strlen($r->content)." bytes <br>";
      on_pages_content_update($r->id,$r->content);
	}
	die("DONE");
}

function page_search_test() {
  echo "<META http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
  $_REQUEST['s']='rack';
  echo page_search();

  $_REQUEST['s']='gallery';
  echo page_search();

  $_REQUEST['s']='сервер';
  echo page_search();
  
  die();

}

function on_pages_content_update($id,&$value) {
   $s = $value;
   $s = strip_for_search($s);
   $s = mb_strtolower($s,"UTF-8");
   db_query("UPDATE pages SET content_search='%s' WHERE id=%d",$s,$id);
}

function strip_for_search($s) {
   $s = strip_tags($s);
   $s = html_entity_decode($s,ENT_COMPAT,"UTF-8");
   $s = preg_replace("|{[^}]*}|","",$s);

   return $s;
}

function page_search() {
  $s = form_post("s");
  $rr = db_fetch_objects(db_query("SELECT * FROM pages WHERE content_search like '%%%s%%' LIMIT 10",$s));
  $o = "";

  if(count($rr)==0) {
    $o .= "Под запрос <strong>$s</strong> не подходит ни одна страница.";
  } else
  
  foreach($rr as $r) {
	 $r->content = strip_for_search($r->content);
	 $p = mb_strpos($r->content_search,strtolower($s),0,"UTF-8");
	 $r->content = mb_substr($r->content,0,$p,"UTF-8")."<strong>".
		 mb_substr($r->content,$p,mb_strlen($s,"UTF-8"),"UTF-8")."</strong>".mb_substr($r->content,$p+mb_strlen($s,"UTF-8"),mb_strlen($r->content),"UTF-8");
	 $start = $p-200;
	 if($start<0) $start = 0;
	 $r->span = mb_substr($r->content,$start,400,"UTF-8");
     $GLOBALS['r'] = $r;
     $o .= template("search");
  }

  $o .= "<div style='padding-top:20px'><a href=search/google&s=".urlencode($s).">Использовать google поиск по сайту</a></div>";
  return $o;

}


function page_search_google() {
  global $base_url;
  Header("Location: http://www.google.com/search?q=".form_post('s')."&sitesearch=".$base_url);
  die();
}
?>
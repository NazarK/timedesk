<?php

/*
  Simple Query Parsing PHP CMS
  (c) 2010 nkcomp.com, Dexosoft, Nazar Kuliyev
*/

/*
  application part 
*/

$tables["top_list"]["fields"][] = "email";
$tables["top_list"]["fields"][] = "time";


$tables["users"]["fields"][] = "username";
$tables["users"]["fields"][] = "password";
$tables["users"]["fields"][] = "email";

function page_home() {
	redir("report");
}
function page_die() {
  die();
}

function page_pi() {
   phpinfo();
   die();
}

function page_db($tablename="") {
    page_header("database");
    if(mysql) {
        if($tablename) {
            flash("table $tablename structure");
            $res = db_query("DESC $tablename");  
            return htmlquery_code($res);
        } else {
            flash("connecting to database");
            $res = db_query("SHOW TABLES");
            return htmlquery_code($res,"<a href=?q=db/[Tables_in_".MYSQL_DB."]>details</a>");
        }
    }

    if(sqlite2) {
        return htmlquery_code(db_query("SELECT * FROM users"));
    }
}

function page_example($action="",$id="") {
  return table_edit("top_list","example",$action,$id);
}

function page_users($action="",$id="") {
  requires_authorization();
  return table_edit("users","users",$action,$id);
}

function page_app_vars() {
  table_create("log");
  table_alter("log","time_time INT(10)");
  table_alter("log","memo VARCHAR(100)");
  table_alter("log","active_check BOOL");
}

function page_start($auto=0) {
  $o = "";
  $memo = db_result(db_query("SELECT memo FROM log  WHERE active_check ORDER BY id desc LIMIT 1"));
  form_start();
  form_input("memo","memo",$memo);
  form_submit("submit","submit");
  $o .= form();

  if(form_post("submit") || $auto) {
	if($auto) $_REQUEST['memo'] = $memo;
    db_query("INSERT INTO log (time_time,active_check,memo) 
	  VALUES(%d,true,'%s')",time(),form_post("memo"));
	$o .= "<br>submitted";
	redir("report");
  }
  return $o;
}

function page_stop() {
  db_query("INSERT INTO log (time_time,active_check)
    VALUES(%d,false)",time());
  redir("report");
}

function page_report($daysago=0) {
  $midnight = @mktime(0, 0, 0, date('n'), @date('j')-$daysago);

  $rr = db_fetch_objects(db_query("SELECT * FROM log WHERE time_time>$midnight"));

  table_start(6);
  $state = 0;
  $total = 0;
  foreach($rr as $r) {
	  $next = db_result(db_query("SELECT time_time FROM log WHERE id>$r->id ORDER BY id LIMIT 1"));
	  if(!$next) $next = time();
	  $span = round(($next-$r->time_time)/(60*60),2);
	  if($r->active_check) {
	    table_add("<input class=active_check type=checkbox>");
	  } else {
	    table_add("");
	  }
	  table_add(@date("H:i:s",$r->time_time));
	  table_add($r->memo);
	  table_add($span," class=span ");
	  $mins = 60*$span;
	  table_add("hour ($mins min)");
	  $h = $span*200;
	  if($r->active_check) $bg = "#0f0";
	  else $bg = "#ddd";
	  table_add("<div style='height:{$h}px;background:{$bg};'>&nbsp;</div>");
	  if($r->active_check) $total += $span;
  }
  $o = table();
  $total_min = 60*$total;
  $o .= "total: $total ($total_min min)<br>";
  $o .= "checked total: <span id=checked_total></span>";
  return $o;
}

function page_toggle() {
  $state = db_result(db_query("SELECT active_check FROM log ORDER BY id DESC LIMIT 1"));

  $o = "";
  if($state) {
     $o = page_stop();
  } else {
     $o = page_start(1);
  }
  
  return $o;
}


function NextAction() {
  $state = db_result(db_query("SELECT active_check FROM log ORDER BY id DESC LIMIT 1"));

  $o = "";
  if($state) {
     return "STOP";
  } else {
	 return "START";
  }
  
}

?>
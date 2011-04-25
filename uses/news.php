<?php

$tables['news']['fields'][] = "date";
$tables['news']['fields'][] = "news_text";
$tables['news']['weight'] = 1;

function page_news_vars() {

	db_query("
CREATE TABLE [news] (
  [id] INTEGER  PRIMARY KEY NOT NULL,
  [date] VARCHAR(120)  NULL,
  [weight] INTEGER,
  [news_text] TEXT)");

}

function page_news_list() {
  page_header("{~НОВОСТИ}");
  $news = db_fetch_objects(db_query("SELECT * FROM news ORDER BY weight DESC"));
  $o = "";
  foreach($news as $r) {
    obj_trans($r);
	$GLOBALS['news'] = $r;
	$o .= template("news_list");
  }
  return $o;
}

function news_date($ofs) {
  $news = db_fetch_object(db_query("SELECT * FROM news ORDER BY weight DESC LIMIT $ofs,1"));
  if($news)
  return fld_trans($news->date);
}

function news_text($ofs,$limit=800) {
  $news = db_fetch_object(db_query("SELECT * FROM news ORDER BY weight DESC LIMIT $ofs,1"));
  if($news) {
    $o = fld_trans($news->news_text);
	if(!$limit) $limit = 800;
    return str_limit($o,$limit);
  }
}

function page_admin_news($act="",$id="") {
  requires_admin();
  use_template("admin");
  $o = table_edit("news","admin/news",$act,$id);
  return $o;
}

function page_news_test() {
  echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8;'>";

  echo news_date(0);
  echo news_text(0);
  echo news_date(1);
  echo news_text(1);
  echo news_date(3);
  echo news_text(3);

  echo page_news_list();


  die();

}

?>
<?php


function page_pages_vars() {
  db_query("CREATE TABLE [pages] (
[id] INTEGER  PRIMARY KEY NOT NULL,
[short] VARCHAR(80)  NULL,
[content] TEXT  NULL,
[content_search] TEXT NULL,
[fixed] BOOLEAN DEFAULT 'false' NULL,
[weight] INTEGER  NULL
)");
  die(" ");
}


function p_quickedit_html($id) {
	  if(admin()) return  "<a style='z-index:9000' target=_blank href=admin/edit/pages/content/$id><img style='z-index:9000' src=images/edit.png></a>";
	  return "";
}

function PageTitle($id) {
	return db_result(db_query("SELECT short FROM pages WHERE id=%d",$id));
}

function page_p($id) {
  $page = db_object_get("pages",$id);
  if($page)
    $o = $page->content;
  else
    $o = "not defined";
  $o .= p_quickedit_html($id);
  replace_my_tags($o); // {href {f {call
  if(form_post("die")) {
    replace_files($o); // {!something.js} {!something.css}
    replace_globals($o); // {!global} {!global}
    translate_parse($o); // {~rus} {~eng}
	die($o);
  }
  return $o;
}

$tables["pages"]["fields"][] = "short";
$tables["pages"]["liveedit"] = 1;
$tables["pages"]["weight"] = 1;

function page_admin_pages($act="",$id="") {
	requires_admin();
	use_template("admin");
	$o = "";
	if($act=="del") {
		$p = db_object_get("pages",$id);
		if($p->fixed=='Y') {
           $act = "-";
		   $o .= '<script>alert("Эту страницу нельзя удалить.")</script>';
		}
	}
	global $table_edit_props;
	$table_edit_props->col_title_show = false;
//	$table_edit_props->new_record_show = false;
//   $table_edit_props->del_record_show = false;
//    $table_edit_props->edit_record_show = false;
    global $base_url;
	$o .= table_edit("pages","admin/pages",$act,$id,"","","",
		"<a href=admin/edit/pages/content/[id]><img src=images/text_edit.png atl='Редактировать' title='Редактировать'></a> <a href={$base_url}p/[id]>{$base_url}p/[id]</a>");
	return $o;
}


function WebPageTitle() {
  if(self_q()=="p") {
	  $t = PageTitle(arg(0));
	  if($t)
	  return "$t - ";
	  else
	  return "";
  } else {
	  return "";
  }
}

?>
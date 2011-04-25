<?php
//set $tables['table']['liveedit'] = true 
//then use this editor on this table
//direct db access through URL - very dangerous
//NK 2011

define("CLOSE_ON_SAVE",false);
function page_admin_edit($table,$field,$id) {
  use_template("admin");
  requires_admin();
  global $tables;
  if(!isset($tables[$table]['liveedit'])) {
	  die("can't edit this table tables[$table]['liveedit'] not set");
  }

  if(form_post("editor1")) {
     $html = form_post("editor1");
	 $html = str_replace('\"','"',$html);
	 $html = str_replace("\'","'",$html);
	 $f = "on_{$table}_{$field}_update";
     if(function_exists($f)) {
		 $f($id,$html);
	 }
     db_query("UPDATE %s SET %s='%s' WHERE id=%d",$table,$field,$html,$id);

	 if(CLOSE_ON_SAVE)
	 die("<script> window.close();</script>");
  }
  $content = db_result(db_query("SELECT %s FROM %s WHERE id=%d",$field,$table,$id));
  $o = template("tinymce","content",$content);
  return $o;
}

?>
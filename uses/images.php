<?php
$tables["images"]["fields"][] = "link";

function page_images_vars() {
	db_query("
CREATE TABLE [images] (
[id] INTEGER  PRIMARY KEY NOT NULL,
[link] VARCHAR(80)  NULL)");

}
function page_admin_images($act="",$id="") {
  requires_admin();
  use_template("admin");

  if($act=="add") {
      if(form_file_uploaded("file")) {
		$fname = $_FILES["file"]['name'];
		db_query("INSERT INTO images (link) VALUES ('')");
		$id = db_last_id();
		$fname = $id.".".fileext($fname);
        form_file_uploaded_move("file","img/".$fname);
		db_query("UPDATE images SET link='img/$fname' WHERE id=%d",$id);
		redir("admin/images");
	  }
	form_start("","post"," enctype='multipart/form-data' ");
    form_file("Файл","file");
	form_submit("Загрузить","submit");
	form_end();
	$o = form();
	return $o;

  }

  if($act=="del") {
    $im = db_object_get("images",$id);
	@unlink("../$im->link");
  }

  $o = table_edit("images","admin/images",$act,$id,"","","","image_func");
  return $o;
}

function image_func($id) {
  $image = db_object_get("images",$id);
  return "<img height=40 src=$image->link>";

}

?>
<?php

function page_menu_vars() {
	
	db_query("
CREATE TABLE [menu] (
[id] INTEGER  PRIMARY KEY NOT NULL,
[parent_id] INTEGER  NULL,
[title] VARCHAR(80)  NULL,
[fixed] BOOLEAN DEFAULT 'false' NULL,
[weight] INTEGER  NULL,
[link] VARCHAR(80)  NULL
)");


}


function menu_path($sub_id) {
	$o = "<a href=?q=admin/menu/edit>Меню</a>&nbsp;";
     $item = db_object_get("menu",$sub_id);
     $menupath = "<a href=?q=admin/menu/edit/$sub_id>$item->title</a>";
	 while($item->parent_id) {
	   $item = db_object_get("menu",$item->parent_id);
	   $menupath = "<a href=?q=admin/menu/edit/$item->id>$item->title</a>"." > $menupath";
	 }
	 $o .= "> $menupath<br>";
	 return $o;
}

function page_admin_menu_edit($parent_id="",$act="",$id="") {
	requires_admin();
	set_lang("other");
	use_template("admin");
    if(!$parent_id) $parent_id = 0;

	$o = "";
	if($act=="del") {
		$rec = db_object_get("menu",$id);
		if($rec->fixed=='Y') {
           $act = "-";
		   $o .= '<script>alert("Эту запись нельзя удалить.")</script>';
		}
	}

    global $tables;

    $tables['menu']['fields'][] = "title";
    $tables['menu']['fields'][] = "link";
	$tables['menu']['weight'] = true;


    if($parent_id) { 
	  $o .= menu_path($parent_id);
	}
    
    $o .= table_edit("menu","admin/menu/edit/$parent_id",$act,$id,"parent_id",$parent_id,"","on_menu"

	  );


	return $o;

}

function on_menu($id) {
  $count = db_result(db_query("SELECT count(1) FROM menu WHERE parent_id=%d",$id));
  $o = "<a href=admin/menu/edit/$id title='Подменю'><img src=images/menu.png></a>";
  if($count) $o .= "($count)";
  return $o;
}



function menu_items($parent_id) {
  $items = db_fetch_objects(db_query("SELECT * FROM menu WHERE parent_id=%d ORDER BY weight",$parent_id));
  return $items;
}

function menu_line($parent_id) {
  $items = menu_items($parent_id);
  $o = "";
  foreach($items as $item) {
	 if(!$item->link) $item->link = "m/$item->id";
     $o .= "<a href=$item->link>$item->title</a>";
  }
  return $o;
}

function page_m($id) {
   $m = db_object_get("menu",$id);

   if(!$m->link) {
     return "Меню - пустая ссылка";
   }
   Header("Location: $m->link");
   die();
}

function menu_vertical($parent_id,$level=0) {
  $items = menu_items($parent_id);
  if($level=="") $level = 0;
  $o = "";
  foreach($items as $item) {
	  $href = $item->link;
	  if(!$href) {
		  $href = "m/$item->id";
	  }
      $o .= "<div class=menu_item level=$level parent_id=$parent_id item_id=$item->id><a href=$href>$item->title</a></div>";
	  $o .= menu_vertical($item->id,$level+1);
  }

  return $o;
}

?>
<?php
/* admin module */
/* call ?q=admin/vars before using to create necessary data structure*/
/* define page_admin() { requires_admin() } */
define("SITE_DOMAIN","vipromotion.kz");

function page_admin_vars() {

  db_query("CREATE TABLE settings (admin_pass VARCHAR(80), admin_email VARCHAR(80))");
  if(db_result(db_query("SELECT COUNT(1) FROM settings"))<1) {
    db_query("INSERT INTO settings (admin_pass) VALUES ('123')");
  }
}

function page_admin_logout() {
  global $apptitle;
  $_SESSION[$apptitle."admin"] = "";
  $o = "Admin logged out.";
  redir("admin/login");
}

function page_admin_email() {
  use_template("admin");
  if(form_post("save")) {
    db_query("UPDATE settings SET admin_email='%s'",form_post("email"));
//    die("<script>window.close()</script>");
  }
  $settings = db_fetch_object(db_query("SELECT * FROM settings LIMIT 1"));
  $o = "<h1>Admin Email</h1>";
  form_start();
  form_input("Email","email",$settings->admin_email);
  form_submit("Save","save");
  form_end();

  $o .= form();
  return $o;
}

function page_admin_pass() {
  use_template("admin");

  $o = "";
  $o .= "<h1>Admin Pass</h1>";

  if(form_post("submit")) {
	$pass = db_result(db_query("SELECT admin_pass FROM settings"));
	if(form_post("curpass")!=$pass) {
		$o .= "Current password is wrong";
		sleep(2);
	} else
    if(form_post("newpass")!=form_post("retype")) {
       $o .= "Retype doesn't match";
	} else {
       db_query("UPDATE settings SET admin_pass='%s'",form_post("newpass"));
	   $o .= "Password has been changed";
	}

  }
  form_start();
  form_password("Current password","curpass");
  form_password("New password","newpass");
  form_password("New retype","retype");
  form_submit("Submit","submit","");
  form_end();
  $o .= form();
  return $o;
}

function admin_email() {
  $email = db_result(db_query("SELECT admin_email FROM settings LIMIT 1"));
  return $email;
}
function page_admin_pass_recover() {
  $email = admin_email();
  $pass = db_result(db_query("SELECT admin_pass FROM settings LIMIT 1"));
  $msg = "Пароль администратора: $pass";
  mail_from($email,"пароль администратора",$msg,"no-reply@".SITE_DOMAIN);
  die_no_template("Пароль выслан на почту администратора.");
}

function page_admin_login() {
  global $apptitle;
  if(isset($_SESSION[$apptitle."admin"]) && $_SESSION[$apptitle."admin"]) {
	  return "Admin is logged in.";
  }
  $o = "<h1>Панель администратора</h1>";


  if(form_post("login")) {
     $pass = db_result(db_query("SELECT admin_pass FROM settings"));
	 if(form_post("pass")==$pass) {
		 $_SESSION[$apptitle."admin"]=1;
		 redir("admin");
	 } else {
         $o .="Sorry, wrong password.";
		 sleep(2);
	 }
  }

  form_start();
  form_password("Пароль","pass","");
  form_submit("Войти","login");
  form_end();

  $o .= form();

  $o .= "";

  die_no_template("<style>input {width:200px} * { font-family: Trebuchet MS; color: #333;} </style><div style='width:100%;text-align:center;'><div style='margin-left:auto;margin-right:auto;width:400px;background:#ddd;margin-top:120px;padding:30px;border-radius:10px;-moz-border-radius:10px;'>$o</div></div>
  <br><a href=admin/pass/recover>Восстановить пароль</a>");

}

function admin() {
  global $apptitle;
  return session_get("admin");
}

function page_admin_table_edit($tablename,$act="",$id="") {
  requires_admin();
  use_template("admin");
  global $tables;
  if(!isset($tables[$tablename]['directedit'])) {
     die("tables[$tablename]['directedit'] missing");
  }
   
  return table_edit($tablename,"admin/table/edit/$tablename",$act,$id);
}

function page_admin_file_edit($filename) {
  requires_admin();
  global $files;
  if(!isset($files[$filename]['directedit'])) {
	  die("files[$filename]['directedit'] missing");
  }

  if(form_post('save')) {
     file_put_contents($filename,form_post('text'));
	 die("<script> window.close(); </script>");
  }

  $txt = file_get_contents($filename);
  
  $o = "<form method=post><textarea name=text style='width:100%;height:80%'>$txt</textarea>";

  $o.= "<br><input type=submit name=save value={~save}><input type=button name=cancel value={~cancel} onclick='window.close()'></form>";


  return $o;

}

function requires_admin() {
  global $apptitle;
  if(admin()) {
    return true;    
  } else {
    $pass = db_result(db_query("SELECT admin_pass FROM settings"));
    if($pass=="") {
      session_set("admin",true);
      return true;
    }
    redir("admin/login");
  }   
}

function page_admin_edit_file($filename) {
  $filename = str_replace("-","/",$filename);
  requires_admin();
  use_template('admin');
  global $files;
  if(!isset($files[$filename]['directedit'])) {
	  die("not set files[$filename]['directedit']");
  }

  if(form_post("save")) {
    file_put_contents($filename,form_post("text"));
	Header("Location: ".form_post("return"));
  }
  form_start();
  form_textarea("","text",file_get_contents($filename),100,20);
  form_hidden("return",$_SERVER['HTTP_REFERER']);
  form_submit("Save","save");

  $o = form();

  return $o;


}

function page_admin() {
  requires_admin();
  use_template('admin');
  return;
}


/* edit table settings */  
function page_admin_settings() {
  requires_admin();
  use_template("admin");
  global $tables;
  $o = "";
  if(form_post("save")) {
    
    $set = "";
    foreach($tables['settings']['fields'] as $field) {
      if($set) $set .= ",";
      $set = "$field='".form_post($field)."'";
    }
    db_query("UPDATE settings SET $set");
    $o .= "<font color=green>changes saved</font>";
  }

  $vals = db_fetch_object(db_query("SELECT * FROM settings LIMIT 1"));
  form_start();
  foreach($tables['settings']['fields'] as $field) {
    form_input($field,$field,$vals->$field);    
  }
  form_submit("Save","save");
  form_end();
  
  $o .= form();  
  return $o;
  
}

?>
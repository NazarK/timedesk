<?php

function frm($name,$email="") {
  if(form_post("submit")) {
	    $message = "";
        foreach($_REQUEST as $key=>$value) {
		  
		  if(str_beg($key,"cb_")) {
			  $message .= "$value\r\n";
		  }

          if(str_beg($key,"f_")) {
			 $caption = $_REQUEST["c_".str_replace("f_","",$key)];
             $message .= "$caption\r\n$value\r\n\r\n";
		  }
		}

		$from = "no-reply@tradecity.kz";
		if(!$email) {
		  $to = setting("admin_email");
		} else {
		  $to = $email;
		}
		$subject = form_post("subject");
		$local = ($_SERVER['REMOTE_ADDR'] == "127.0.0.1");
		$parts = explode(";",$email);

		if(form_file_uploaded("uploadedfile")) {
		   $tmp = $_FILES['uploadedfile']['tmp_name'];
		   $fname = $_FILES['uploadedfile']['name'];
           if($local) {
             echo("<pre>$message tmp[$tmp] fname[$fname]</pre>");
		   } else {
			 foreach($parts as $reciever) {
               mail_attach($from,$reciever,$subject,$message,$tmp,$fname);
			 }
		   }
		} else {
           if($local) {
             echo("<pre>$message</pre>");
		   } else {
			 foreach($parts as $reciever) {
		       mail_from($reciever,$subject,$message,$from);
		     }
		   }
		}
		return form_post("success");

  }
  return template("frm_".$name);
}

function page_form($name) {
  return frm($name);
}

?>
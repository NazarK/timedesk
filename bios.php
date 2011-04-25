<?php
/*
  Simple Query Parsing PHP CMS
  (c) 2010 nkcomp.com, Dexosoft, Nazar Kuliyev, C.I.S., Kazakhstan
*/


/// BIOS
///
// now version is file size!!!!
//1.15 - db_models_validate, ln_inc, ln_dec

//1.14 - HTTPPost, HTTPPostFix, HTTP_POST_DOUBLE_SLASH - double slash replace
//1.13 - table_Edit - actionstring - can be function(id) now
//1.12 - table_edit - added action string
//1.11 - table_edit, db_list
//1.10 - form_list fix

//1.09 - DICT, T()
//1.08
//form_object_edit
//form_object_add
//db_object_del

//1.07
//1.06
// 1.05
// added static_
// added table_

/** version 1.01 - Feb 20, 2009

// added sqlite2 support


/**  1FCMS v1.00 **/

//functions go first, main() is at the end


/**
* Table Editor
* (inspired by Ruby)
* edit,delete,add new record, paging options
* TableBrowser::browse("test_table","id","id,username as user_name",10,"EVDN");
* "EVDN" - Edit, View, Delete, New
* @author Nazar Kuliev 09/2007 (projectplaylist.com)
* @package Utilities
* @version 1.05
*/

//1.06 - show serialized data
//1.05 - browsetable added
//1.04 - fix in record view
//1.03 - 13/09/2007 - $option_showtitle added, little comments added
//1.02 -  pagesize=0 - means no total count
//1.01 - browse_echo removed, mainsql parameter added to browse method

function errormsg($s) {
  return "<span class='errormsg'>$s</span>";
}
function sql($s,$s1="",$s2="",$s3="",$s4="",$s5="",$s6="",$s7="",$s8="",$s9="",$s10="") {
   return db_query($s,$s1,$s2,$s3,$s4,$s5,$s6,$s7,$s8,$s9,$s10);
}

function ql($s,$s1="",$s2="",$s3="",$s4="",$s5="",$s6="",$s7="",$s8="",$s9="",$s10="") {
   return db_query($s,$s1,$s2,$s3,$s4,$s5,$s6,$s7,$s8,$s9,$s10);
}

assert_options(ASSERT_BAIL,true);

function T($s) {
  global $DICT;
  
  if(empty($DICT) || empty($DICT[$s])) {
    return $s;
  } else {
    return $DICT[$s];
  }
}

//*
//*  validate tables array - database structure, check if all fields do exist in database 
//*

function db_models_validate() {
 global $tables;
 foreach($tables as $tablename=>$table) {
   ln("table:".$tablename);
   ln_inc(); 
   foreach($table["fields"] as $field) {
     //ln($field);
     //ln("running query");
     $res = db_query("SELECT $field FROM $tablename LIMIT 1");
   }
   ln_dec(); 
 }
}



/**
* Simple function for browsing table
* calling TableBrowser, assuming that first field in table description is keyfield
*
* @param string $tablename Table in currently connected database
* @param string $mode "EVDN" Edit,View,Delete,New by default
*/

function browsetable_code($tablename,$mode="EVDN",$actstring="") {
    ob_start();
    browsetable($tablename,$mode,$actstring);
    $s = ob_get_contents();
    ob_end_clean();
    return $s;
}

function browsetable($tablename,$mode="EVDN",$actionstring="") {
    $fields = TableBrowser::table_fields($tablename);
    TableBrowser::browse("",$tablename,$fields[0],"*",10,$mode,$actionstring);
}


function browsequery($query,$maintable,$fieldstoshow="*",$actionstring="",$perpage=10) {
    ob_start();
    TableBrowser::browse($query,$maintable,"id",$fieldstoshow,$perpage,"",$actionstring);
    $s = ob_get_contents();
    ob_end_clean();
    return $s;
}

/**
* Get variable value from script get query string
*
* Parses $_SERVER['QUERY_STRING']
* @param string $variable Variable name
* @param string $default Default value, if variable wasn't found in query string
* @return string Variable value
*/
function querystring_get($variable,$default=0) {
    $querystring = $_SERVER['QUERY_STRING'];
    parse_str($querystring,$values);
    if(!isset($values[$variable])) return $default;
    else return $values[$variable];
}
/**
* Set variable in get query string
*
* If variable value already in query string it will be modified,
* else variable value will be added to end of query string, original script
* query string stays same, modified is returned
*
* @param string $variable Variable name
* @param string $value Variable value
* @return string Modified query string (with variable value added)
*/
function querystring_set($variable,$value) {
    $querystring = $_SERVER['QUERY_STRING'];
    $pos = strpos($querystring,"&$variable=");
    if($pos===FALSE) {
        if(strpos($querystring,"$variable=")===0)
        $pos=0;
    } else {
        $pos++;
    }
    if($pos===FALSE) {
        if($value=='') return $querystring;
        if($querystring=="") return "$variable=$value";
        else return $querystring."&$variable=$value";
    }


    $endpos = strpos($querystring,"&",$pos);
    if($endpos===FALSE) $endpos=strlen($querystring);

    if($value=='') {
        return substr($querystring,0,max(0,$pos-1)).substr($querystring,$endpos);
    } else {
        return substr($querystring,0,$pos)."$variable=$value".substr($querystring,$endpos);
    }
}

/**
* Show Data browse grid
*/

class TableBrowser {

    var $option_showtitle = true;
    var $option_selfstyles = true;
    var $option_shownumbers = true;

    function table_fields($tablename) {
        $description = mysql_query("DESC $tablename");
        $return = array();
        while($line = mysql_fetch_array($description)) {
            $fieldname = $line['0'];
            $return[]=$fieldname;
        }
        return $return;
    }

    function table_column_widths($tablename) {

        $description = mysql_query("DESC $tablename");
        $return = array();
        while($line = mysql_fetch_array($description)) {
            $fieldname = $line['0'];
            $fieldwidth = $line['1'];
            preg_match("|\((.*)\)|",$fieldwidth,$match);

            $fieldwidth = @$match[1];
            $return[$fieldname]=$fieldwidth;
        }
        return $return;

    }

    function recorddelete($tablename,$keyfield,$id_value) {

        $_SERVER['QUERY_STRING'] = querystring_set("deleteid","");
        $sql = 	"DELETE FROM $tablename WHERE $keyfield=$id_value";
        //  echo $sql;
        mysql_query($sql);
        $backurl = $_SERVER['QUERY_STRING'];
        Header("Location: ?$backurl");
    }

    function recordview($tablename,$keyfield,$keyfieldvalue) {

        $sql = "SELECT * FROM $tablename";
        if(!isset($this) || $this->option_selfstyles) {
            echo "<style>
        a { color: #000; }
        a:visited { color: #666; }
        a:hover { color: #fff; background-color:#000; }
            </style>";
        }

        echo "<div style='font-family:verdana'>";
        $title = "SHOWING ".str_replace("_"," ",$tablename);
        echo "<title>$title</title>";
        echo "<h1>$title</h1>";
        $previd = @mysql_result(mysql_query("SELECT $keyfield FROM $tablename
        WHERE $keyfield<'$keyfieldvalue' ORDER BY $keyfield DESC LIMIT 1"),0);
        $nextid = @mysql_result(mysql_query("SELECT $keyfield FROM $tablename
        WHERE $keyfield>'$keyfieldvalue' ORDER BY $keyfield LIMIT 1"),0);
        if($previd || $nextid) {
            echo "<br>";
            $prevlink = querystring_set("viewid",$previd);
            $nextlink = querystring_set("viewid",$nextid);
            if($previd) echo "<a href=?$prevlink><<</a> ";
            if($nextid) echo "<a href=?$nextlink>>></a>";
            echo "<br>";
        }

        $backurl = querystring_set("viewid","");
        if(strpos(strtolower($sql)," where ")!==FALSE) {
            $sql .= " AND $keyfield='$keyfieldvalue'";
        } else {
            $sql .= " WHERE $keyfield='$keyfieldvalue'";
        }

        $result = mysql_query($sql);
        $line = mysql_fetch_object($result);
        $widths = TableBrowser::table_column_widths($tablename);
        foreach($line as $colname => $value) {
            $size = "";
            echo "<br><strong>$colname</strong>";
            if(strpos($colname,"serialize")!==FALSE ) {
                echo "<pre>";
                $v = @unserialize($value);
                print_r($v);
                echo "</pre>";
            } else {
                echo "<pre>$value</pre>";
            }
        }

        echo "<br><a href=?$backurl>Back</a>";
        echo "</div>";

    }

    function recordeditor($tablename,$keyfield,$fields,$id_value,$backurl=FALSE) {
        $sql = "SELECT $fields FROM $tablename";
        if(querystring_get("do",'')=='update') {

            parse_str($_SERVER['QUERY_STRING']);

            $setsql = "";
            foreach($field as $colname=>$value) {
                if($setsql!=="") $setsql .= ", ";
                $setsql .= "$colname='$value'";
                $_SERVER['QUERY_STRING'] = querystring_set("field%5B$colname%5D","");

            }
            $_SERVER['QUERY_STRING'] = querystring_set("editid","");
            $_SERVER['QUERY_STRING'] = querystring_set("do","");

            mysql_query("UPDATE $tablename SET $setsql WHERE $keyfield='$id_value'");
            //	$backurl = $_SERVER['QUERY_STRING'];

            Header("Location: ?$backurl");
            //echo "<br>record updated <br><a href=?$backurl>back</a>";
            return;
        }
        if(!isset($this) || $this->option_selfstyles) {

            echo "<style>
        a { color: #000; }
        a:visited { color: #666; }
        a:hover { color: #fff; background-color:#000; }
            </style>";
        }
        echo "<div style='font-family:verdana'>";
        $title = "EDITING ".str_replace("_"," ",$tablename);
        echo "<title>$title</title>";
        echo "<h1>$title</h1>";
        echo "<form>";
        //drupal
        if($_GET['q']) {
            echo "<input type=hidden name='q' value=".$_GET['q'].">";
        }
        if(strpos(strtolower($sql)," where ")!==FALSE) {
            $sql .= " AND $keyfield='$id_value'";
        } else {
            $sql .= " WHERE $keyfield='$id_value'";
        }

        $result = mysql_query($sql);
        $line = mysql_fetch_object($result);
        $widths = TableBrowser::table_column_widths($tablename);

        $aliases = TableBrowser::fieldaliases($fields);
        foreach($aliases as $realname => $viewname) {
            if($realname==$keyfield) continue;
            $size = "";
            if(isset($widths[$realname])) {
                $size = " size=".min($widths[$realname],120);
            } else {
                $size= " size=400";
            }
            $caption = str_replace("_"," ",$viewname);
            if(substr($realname,strlen($realname)-5)=="_text") {
                echo "<br>$caption<br><TEXTAREA ROWS=6 COLS=110 name=field[$realname]>".$line->$viewname."</TEXTAREA><br>";
            } else {
                echo "<br>$caption<br><input name=field[$realname] value='".$line->$viewname."'$size><br>";
            }
        }


        echo "<br><input type=submit name=do value=update>";
        echo "<input type=hidden name=editid value=$id_value>";
        echo "<input type=hidden name=backurl value=$backurl>";
        echo "</form>";
        if($backurl!==FALSE) {
            echo "<br><a href=?$backurl>Back</a>";
        }
        echo "</div>";


    }
    
    function fieldaliases($fields) {
        $result = array();

        //remove function calls brackets as there are commas inside of it like SUBSTR(field,40)
        $fields = str_replace("(","<!--",$fields);
        $fields = str_replace(")","-->",$fields);
        $fields = strip_tags($fields);
        $fieldnames = explode(",",$fields);
        foreach($fieldnames as $fieldname) {
            $fieldname_parse = explode(" as ",$fieldname);
            $fieldname = trim($fieldname_parse[0]);
            $caption = $fieldname;
            if(isset($fieldname_parse[1])) {
                $caption = trim($fieldname_parse[1]);
            }
            $result[$fieldname] = $caption;
        }
        return $result;
    }

    function recordnew($tablename,$keyfield,$fields) {
        if(querystring_get("do",'')=='create') {
            parse_str($_SERVER['QUERY_STRING']);

            $fields = "";
            $values = "";
            foreach($field as $colname=>$value) {
                $fields[] = $colname;
                $values[] = "'".mysql_real_escape_string($value)."'";
            }
            $fields = implode(",",$fields);
            $values = implode(",",$values);


            $q = "INSERT INTO $tablename ($fields) VALUES ($values)";
            $res = mysql_query($q);


            if($res===FALSE) {
                echo "Error while adding record.";
                echo "<a href=?$backurl>Back</a>";
                return;
            }

            Header("Location: ?$backurl");
            //echo "<br>record updated <br><a href=?$backurl>back</a>";
            return;
        }
        if(!isset($this) || $this->option_selfstyles) {

            echo "<style>
        a { color: #000; }
        a:visited { color: #666; }
        a:hover { color: #fff; background-color:#000; }
            </style>";
        }
        echo "<div style='font-family:verdana'>";
        $title = "NEW ".str_replace("_"," ",$tablename)." record";
        echo "<title>$title</title>";
        echo "<h1>$title</h1>";
        echo "<form>";
        //drupal
        if($_GET['q']) {
            echo "<input type=hidden name=q value=".$_GET['q'].">";
        }

        if($fields=='*' || $fields=='') {
            $widths = TableBrowser::table_column_widths($tablename);
            foreach($widths as $colname => $width) {
                if($colname==$keyfield) {
                    continue;
                }
                if(isset($widths[$colname])) {
                    $size = " size=".min($widths[$colname],120);
                }
                echo "<br>$colname<br><input name=field[$colname] $size><br>";

            }

        } else {
            $aliases = TableBrowser::fieldaliases($fields);

            $fieldnames = explode(",",$fields);
            $widths = TableBrowser::table_column_widths($tablename);
            foreach($aliases as $fieldname=>$caption) {
                if($fieldname==$keyfield) {
                    continue;
                }
                if(substr($fieldname,strlen($fieldname)-5)=="_text") {
                    echo "<br>$caption<br><TEXTAREA ROWS=6 COLS=110 name=field[$fieldname]></TEXTAREA><br>";
                } else {
                    $size = "";
                    if(isset($widths[$fieldname])) {
                        $size = " size=".min($widths[$fieldname],120);
                    }
                    $caption = str_replace("_"," ",$caption);
                    echo "<br>$caption<br><input name=field[$fieldname] $size><br>";
                }
            }
        }

        echo "<br><input type=submit name=do value=create>";
        $backurl = querystring_set("new","");
        echo "<input type=hidden name=new value=1>";
        echo "<input type=hidden name=backurl value=$backurl>";
        echo "</form>";
        if($backurl!==FALSE) {
            echo "<br><a href=?$backurl>Back</a>";
        }
        echo "</div>";


    }

    /**
    * Main method
    *
    * displays table data with actionstring and optional edit, delete, show and new operation links
    *
    * @param string $mainsql Main SQL text used to render data, if mainsql is "" simple select from table will be used
    * @param string $tablename Main table name, all operations (delete,new) will be performed on it
    * @param string $keyfield Key field name in table
    * @param string $fields List of fields to show from main sql, * may be used, also sql 'as' keyword allowed for aliasing
    * @param int $pagesize Page size for page display
    * @param string $mode String containing E,V,D,N characters for Edit, View, Delete, New operations
    * @param string $action_template_string Action string - ID will be replaced with keyfield value, [FIELDNAME] with FIELD values
    * @return void Return nothing, generates output to browser (echo)
    */
    function browse($mainsql,$tablename,$keyfield='id',$fields='*',$pagesize=10,$mode="EVDN",$action_template_string="")    {

        if(querystring_get("page","")!=="") {
            $pagesize = querystring_get("page","");
        }
        if(strpos($fields,"*")!==FALSE) {
            $fields_list = TableBrowser::table_fields($tablename);
            $fields_list = implode(",",$fields_list);
            $fields = str_replace("*",$fields_list,$fields);
        }

        $mode = strtoupper($mode);

        if(!$mainsql) {
            $sql = "SELECT $fields,$keyfield FROM $tablename ORDER BY $keyfield";
        } else {
            $sql = $mainsql;
        }

        if(strpos($mode,"E")!==FALSE && querystring_get("editid","")!="") {
            $editid = querystring_get("editid");
            TableBrowser::recordeditor($tablename,$keyfield,$fields,$editid,querystring_set("editid",""));
            return;
        }

        if(strpos($mode,"V")!==FALSE && querystring_get("viewid","")!="") {
            $viewid = querystring_get("viewid");
            TableBrowser::recordview($tablename,$keyfield,$viewid);
            return;
        }


        if(strpos($mode,"D")!==FALSE && querystring_get("deleteid","")!="") {
            $deleteid = querystring_get("deleteid");
            TableBrowser::recorddelete($tablename,$keyfield,$deleteid);
            return;
        }

        if(strpos($mode,"N")!==FALSE && querystring_get("new","")!="") {
            TableBrowser::recordnew($tablename,$keyfield,$fields);
            return;
        }

        echo "<div style='font-family:verdana'>";
        $page = 1;
        $sql_with_limit = strpos($sql," LIMIT ");

        //show title if call was :: or object option_showtitle==true
        if(!isset($this) || $this->option_showtitle) {
            $title = "LISTING ".str_replace("_"," ",$tablename);
            echo "<title>$title</title>";
            echo "<h1>$title</h1>";
        }
        $sql_full = $sql;
        if($pagesize)
        if($sql_with_limit===FALSE) {
            $page = querystring_get("p",'1');

            if($page==1) $sql_limit = " LIMIT $pagesize";
            else
            $sql_limit = " LIMIT ".($page-1)*$pagesize.",".$pagesize;
            $sql_full = $sql.$sql_limit;

        }

        $result = mysql_query($sql_full);

        $count_sql = "SELECT count(1) ".substr($sql,strpos(strtoupper($sql)," FROM "),strlen($sql));

        $total_count = 0;
        if($pagesize)
        $total_count = mysql_result(mysql_query($count_sql),0);
        if($sql_with_limit===FALSE)
        if($pagesize)
        {
            if($page==1) echo "Records: 1-".min($pagesize,$total_count);
            else echo "Records: ".(($page-1)*$pagesize+1)." - ".min(($page-1)*$pagesize+mysql_num_rows($result),(($page+1)*$pagesize));
            echo " of $total_count";
        }

        if(!isset($this) || $this->option_selfstyles) {

            echo "<style>
        a { color: #000; }
        a:visited { color: #666; }
        a:hover { color: #fff; background-color:#000; }
            </style>";
        }
        $firstline = true;
        $record = ($page-1)*$pagesize;



        $aliases = TableBrowser::fieldaliases($fields);
        echo "<table style='font-family:verdana' border=0  cellpadding=1 cellspacing=1>\n";
        ///SHOWING DATA
        if(mysql_num_rows($result)==0) {
            echo "<tr><td>No data to show";
        }
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {

            $fieldstoshow = $aliases;
            if($fields=='*') {
                $fieldstoshow = $line;
            }

            //TABLE HEADERS
            if($firstline) {
                $widths = TableBrowser::table_column_widths($tablename);

                echo "<tr style='background:#000000;color:#ffffff;font-weight:bold;'>\n";
                if(!isset($this) || $this->option_shownumbers) {
                    echo "<th width=30>#";
                }
                foreach($fieldstoshow as $field=>$value) {
                    if($fields=='*') {
                        $colname = $field;
                        $col = $value;
                    } else {
                        $colname = str_replace("_"," ",$value);
                        $col = $line[$field];
                    }
                    $width = "";
                    if(isset($widths[$colname])) {
                        $width = " width=".min((8*$widths[$colname]),200);
                    }
                    $viewname = str_replace("_"," ",$colname);
                    echo "<th $width>$colname";
                }
                $firstline = false;
                if(strpos($mode,"V")!==FALSE || strpos($mode,"E")!==FALSE || strpos($mode,"D")!==FALSE || $action_template_string) {
                    echo "<td>";
                }
            }

            echo "\t<tr>\n";


            $record++;
            if(!isset($this) || $this->option_shownumbers) {
                echo "<td valign=top>$record";
            }

            //showing data
            foreach($fieldstoshow as $field => $value) {

                if($fields=='*') {
                    $colname = $field;
                    $col = $value;
                } else {
                    $colname = $field;
                    $col = $line[$field];
                }

                if($colname=="day") {
                    $col = strftime("%d/%m/%Y %a %H:%M:%S",$col);
                }
                if(strpos($colname,'_time') || $colname=="time" || $colname=="creationtime" || $colname=="timestamp") {
                    if($col==0)
                    $col = '-';
                    else
                    $col = strftime("%d/%m/%Y %H:%M:%S",$col);
                }
                if($colname=='period')
                $col = TimePeriodShort($col);
                if(strlen($action_template_string)!=0 && $colname=='id') {
                    continue;
                }
                if(strlen($col)==0) $col = "-";

                if(strpos($field,"serialize")!==FALSE) {
                    $col = @unserialize($col);
                    $col = "<pre>".print_r($col,true)."</pre>";
                }

                echo "<td valign=top>$col";
            }


            if(strpos($mode,"E")!==FALSE || strpos($mode,"V")!==FALSE || strpos($mode,"D")!==FALSE || $action_template_string) {
                echo "<td>";
            }

            //EDIT TABLE LINKS
            if(strpos($mode,"E")!==FALSE || strpos($mode,"V")!==FALSE || strpos($mode,"D")!==FALSE) {
                if(!isset($line[$keyfield])) {
                    echo("</table>EXCEPTION: Keyfield [$keyfield] is not defined in mainsql (browse function - first parameter).");
                    return;
                }

                $editurl = querystring_set("editid",$line[$keyfield]);
                $deleteurl = querystring_set("deleteid",$line[$keyfield]);
                $viewurl = querystring_set("viewid",$line[$keyfield]);
                if(strpos($mode,"E")!==FALSE)
                echo "<a href=?$editurl>Modify</a>";
                echo " ";
                if(strpos($mode,"V")!==FALSE)
                echo "<a href=?$viewurl>Show</a>";
                echo " ";
                if(strpos($mode,"D")!==FALSE)
                echo "<a onclick=\"return confirm('Are you sure?');\" href=?$deleteurl>Delete</a>";
            }

            //ACTION STRING
            if(strlen($action_template_string)!=0) {
                $id = $line[$keyfield];
                $actionstring = str_replace("ID", urlencode($id), $action_template_string);
                foreach($line as $colname => $value) {
                    $actionstring = str_replace("[$colname]",$value,$actionstring);
                }
                echo " $actionstring";
            }

        }

        echo "</table>\n";

        // PAGES
        if($pagesize)
        if($sql_with_limit===FALSE && $total_count>$pagesize) {
            if(!$page) $page = 1;
            echo "<table style='font-family:verdana'>";
            echo "<tr>";
            echo "<td>";
            $query_previous = querystring_set("p",$page-1);
            $query_next = querystring_set("p",$page+1);
            $query_first = querystring_set("p",1);
            $lastpage = floor(($total_count-1)/$pagesize)+1;
            $query_last = querystring_set("p",$lastpage);
            if($page>1) {
                echo "<a href=?$query_previous><<</a> ";
                echo " <a href=?$query_first>first</a> ";
            } else {
                //echo "Previous";
            }

            $range = 10;
            $range = querystring_get("range",$range);
            for($i=max(1,$page-$range);$i<min($page+$range,($total_count/$pagesize)+1);$i++) {
                $query_page = querystring_set("p",$i);
                if($page==$i) {
                    echo " $i";
                } else {
                    echo " <a href=?$query_page>$i</a>";
                }
            }
            if(mysql_num_rows($result)==$pagesize && $total_count!=(($page-1)*$pagesize+mysql_num_rows($result))) {
                echo " <a href=?$query_last>last</a>";
                echo " <a href=?$query_next>>></a>";
            }
            else {
                //echo "Next";
            }
            echo "</table>";
        }
        $newurl = querystring_set("new",1);
        if(strpos($mode,"N")!==FALSE)
        echo "<table><tr><td><a href=?$newurl>New ".str_replace("_"," ",$tablename)." record</a></table>";

        echo "</div>";
    }

}


$log = "";
function log_message($s) {
    global $log;
    $log .= $s;
}

function flash($s) {
    log_message($s);

}




function database_table_fields($tablename) {
    $description = mysql_query("DESC $tablename");
    $return = array();
    while($line = mysql_fetch_array($description)) {
        $fieldname = $line['0'];
        $return[]=$fieldname;
    }
    return $return;
}

function db_object_set($tablename,$object) {
    database_object_set($tablename,$object);
}

//works only with mysql
function database_object_set($tablename,$object) {

    $object_original = database_object_get($tablename,$object->id);

    $fields = database_table_fields($tablename);
    $fields_sql  = "";
    foreach($fields as $field) {
        if(strtolower($field)=="id") continue;
        if($object_original->$field == $object->$field) continue;
        if($fields_sql) {
            $fields_sql .= ", ";
        }
        $fields_sql .= " $field='".$object->$field."' ";
    }

    if($fields_sql) {
      $sql = "UPDATE $tablename SET $fields_sql WHERE id=$object->id";
      db_query($sql);
    }
}

function database_fixslashes($text) {
    $text = str_replace("\\'","'",$text);
    $text = str_replace("\\\"","\"",$text);
    $text = str_replace("\\\\","\\",$text);
    return $text;
}

function db_object_get($tablename,$idvalue,$props=false,$ignoretables_list="") {
    global $database_object_get_tables;
    if($ignoretables_list) {
      $database_object_get_tables = explode(",",$ignoretables_list);
      foreach($database_object_get_tables as &$table) $table = trim($table);
    }
    $res = database_object_get($tablename,$idvalue,$props);
    $database_object_get_tables = array();
    return $res;
}

function connected_table($tablename,$subtable) {
  global $tables;
  $res = $subtable;
  if(!isset($tables[$subtable])) {
    $res = str_prefix($tablename).$subtable;
  }
  if(!isset($tables[$res])) return "";
  else 
  return $res;
}

function str_no_prefix($s,$prefix) {
  return substr($s,strlen($prefix),strlen($s));
}

function db_fetch_objects($rr) {
  $res = array();
  while($r = db_fetch_object($rr)) {
    $res[] = $r;
  }
  return $res;
  
}

function db_fetch_key_value_list($rr,$value_field_name) {
   $res = array();
   while($r = db_fetch_object($rr)) {
      $res[$r->id] = $r->$value_field_name;
   }
   return $res;
}

function table_fields($tablename) {
  global $tables;
      $fields = array();
      if(isset($tables[$tablename]['fields']))
        $fields = array_merge($fields,$tables[$tablename]['fields']);
      if(isset($tables[$tablename]['fields_hidden']))
        $fields = array_merge($fields,$tables[$tablename]['fields_hidden']);
        
        return $fields;
  
}

$fields_stack = array();
$database_object_get_tables = array();

function database_object_get($tablename,$idvalue,$props=false) {
  $o = db_fetch_object(db_query("SELECT * FROM $tablename WHERE id=%d",$idvalue));
  if($o)
  if($props) {
  
    ///get objects for _id fields
    $fields = table_fields($tablename);
    foreach($o as $field=>$field_value) {
      global $fields_stack;
      if(array_search($field,$fields_stack)!==FALSE) continue;
      if(str_end($field,"_id")) {
         $subtable = str_start($field,"_id")."s";
         $subtable = connected_table($tablename,$subtable);
         if($subtable) {
            $member = str_start($field,"_id");
              $prop = database_object_get($subtable,$field_value);
              if($prop)
              foreach($prop as $propname=>$propvalue) {
               $o_prop_name = $propname;
               $o->$o_prop_name = $propvalue;
              }
         }
      }
    }
    
    ///get linked tables
    
    global $tables;
    foreach($tables as $table=>$tableobject) {
      $this_object_link = str_start($tablename,"s")."_id";
      $this_object_prefix = str_prefix($tablename);
      $link_table_prefix = str_prefix($table);
      $table_no_prefix = $table;
    
      
      if($this_object_prefix == $link_table_prefix) {
        $this_object_link = str_no_prefix($this_object_link,$this_object_prefix);
        $table_no_prefix = str_no_prefix($table_no_prefix,$this_object_prefix);
      }

      $ignore_the_table = true;
      global $database_object_get_tables;

      if(count($database_object_get_tables)==0) {
        $ignore_the_table = false;
      } else {
          foreach($database_object_get_tables as $get_table) {
            if($get_table==$table) {
              $ignore_the_table = 0;
              break;
            }
          }
      }
          
      if($ignore_the_table===true) {
        continue;
      }
          
      $fields = table_fields($table);
      foreach($fields as $fieldname) {
        if($fieldname == $this_object_link) {
          $rr = db_query("SELECT id FROM $table WHERE $fieldname = $idvalue");
          global $fields_stack;
          $fields_stack[] = $fieldname;
          $propname = $table;
          if($this_object_prefix == $link_table_prefix) {
             $propname = str_no_prefix($table,$this_object_prefix);
          }
          $subobjects = array();
      
          while($r = db_fetch_object($rr)) {
            $subobject = database_object_get($table,$r->id,true);
            $subobjects[] = $subobject;
          }
          array_pop($fields_stack);

          $o->$propname = $subobjects;
        }
      }
      
      
      
    }
  }  
  return $o;
}


//convert sqlite sql dialect to mysql
function db_sqlite2mysql_sql($query) {

  $query = trim($query);
  if(strtolower($query)=="vacuum") return "";
  if(strtolower(substr($query,0,6))=='insert') {
    $low = strtolower($query);

    if(strpos($low,"values (null,") && strpos($low,"(id,")) {
        $query = str_ireplace("values (null, ","values (",$query);
        $query = str_ireplace("(id, ","(",$query);
    }
    
    if(strpos($low,"values(null,") && strpos($low,"(id,")) {
        $query = str_ireplace("values(null,","values(",$query);
        $query = str_ireplace("(id,","(",$query);
    }
    
    if(strpos($low,"values(null)") && strpos($low,"(id)")) {
        $query = str_ireplace("values(null)","",$query);
        $query = str_ireplace("(id)","",$query);
    }
    
  }
  return trim($query);  

}

$db_query_error_function = "db_die";

function db_die($s) {
  if(str_end(form_post("q"),'/vars')) {
	  echo "vars created<br>";
  } else
      die($s);
}

function db_query($query) {
	@prf_start("db_query");
	@prf_start(substr($query,0,20));
    global $db_query_error_function;
    
    $args = func_get_args();
    array_shift($args);
    if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
        $args = $args[0];
    }
    db_query_callback($args, TRUE);

    if(mysql) {
        $query = db_sqlite2mysql_sql($query);
    }

    if($query=="") return;
    
    $query = preg_replace_callback('/(%d|%s|%%|%f|%b)/', 'db_query_callback', $query);

    if(mysql) {
        $res = mysql_query($query);
        if(!$res) $db_query_error_function("<h1>MYSQL ERROR</h1><br> $query<br>".mysql_error());
    }

    if(sqlite2) {
        global $dbhandle;
        $res = sqlite_query($dbhandle,$query);
        if($res==FALSE) {
            $db_query_error_function("<h1>SQL ERROR</h1><br> $query<br>".sqlite_error_string($dbhandle));
        }
    }
    global $sqllog;
    $sqllog .= $query."<br>";
	@prf_end();
	@prf_end();
    return $res;

}

function db_query_callback($match, $init = FALSE) {
    static $args = NULL;
    if ($init) {
        $args = $match;
        return;
    }

    switch ($match[1]) {
        case '%d': // We must use type casting to int to convert false/null/(true?)
        return (int) array_shift($args); // We don't need db_escape_string as numbers are db-safe
        case '%s':
        if(mysql)
        return mysql_real_escape_string(array_shift($args));
        if(sqlite2)
        return sqlite_escape_string(array_shift($args));
        case '%%':
        return '%';
        case '%f':
        return (float) array_shift($args);
    }
}

function db_num_rows($result) {
    if ($result) {
        if(mysql) {
          $n = mysql_num_rows($result);
          return $n;
        }
        if(sqlite2)
          return sqlite_num_rows($result);
    } else return 0;
}

function db_result($result, $row = 0) {
    if(sqlite2) {
        return sqlite_fetch_single($result);
    }

    if(mysql) {
        if ($result && mysql_num_rows($result) > $row) {
            return mysql_result($result, $row);
        }
    }

}

function db_fetch_object($result) {
    if ($result) {
        if(mysql)
        return mysql_fetch_object($result);
        if(sqlite2)
        return sqlite_fetch_object($result);
    }
}

function db_fetch_array($result) {
    if ($result) {
        if(mysql)
        return mysql_fetch_array($result, MYSQL_ASSOC);
        if(sqlite2)
        return sqlite_fetch_array($result, SQLITE_ASSOC);
    }
}

//should be processed on include
/*if(isset($_GET['q']) && $_GET['q']=='user/logout') {
  $_SESSION['userid'] = "";
}*/

//this will be called on query line processing
/*function page_user_logout() {
  log_message("You were logged out.");
}*/

function page_user_logout() {
   $_SESSION['userid'] = "";
   $_SESSION['ac_type'] = "";
   unset($_SESSION['ext_username']);
   unset($_SESSION['ext_avatar']);
   redir("");
   die();
}

if(isset($_GET['q']))
if($_GET['q']=='user/logout') {
    $_SESSION['userid'] = "";
    $GLOBALS['pageheader'] = "Log out";
}


function user_notify($userid,$message) {
    //email should be here
}

function user_authorized() {
    $authorized = isset($_SESSION['userid']) && strlen($_SESSION['userid'])>0;
    return $authorized;
}
function user_pass_generate() {
    for($i=0;$i<5;$i++) {
        if(rand(0,1)) {
            $newpass .= chr(rand(ord('a'),ord('z')));
        } else {
            $newpass .= chr(rand(ord('A'),ord('Z')));
        }

    }
    return $newpass;
}

function page_user_login() {
	if(user_authorized()) {
		redir("");
	}
    $output = "";

    if(function_exists("on_user_login")) on_user_login();

    //authorization
    if(form_post('username')) {
        $username = $_POST['username'];
        $pass = $_POST['password'];
        $res = db_result(db_query("SELECT id FROM users WHERE email='%s' AND password='%s' LIMIT 1",$username,$pass));
        if($res) {
            $_SESSION['userid'] = $res;
            log_message("Loged in.");
			if(form_post("redir"))
		      redir(form_post("redir"));
		    else
              redir(""); //to refresh menu
            return;
        } else {
            $output .= errormsg("Login failed");
			sleep(1);
        }
    } 

    $GOBALS['log']="&nbsp;";
    form_start("?q=user/login","post"," name=user_login_form ");
    form_input("{~Email}","username",form_post("username"),"","");
    form_password("{~Password}","password","","","");
	global $form;
	$form .= "<input type=submit value='submit' style='width:0px;height:0px;visibility:hidden;'>";
	if(form_post("redir"))
	  $form .= "<input type=hidden name=redir value='".form_post("redir")."'>";
    form_end();
    $output .= form();

    $output .= "<div style='position:absolute;' id=login_btn ><img style='padding-bottom:10px;cursor:pointer' onclick='document.user_login_form.submit()' alt='login' src=slice/login_all.png/835/667/146/46></div>";
    $output .= "<div style='position:absolute;padding-bottom:10px;' id=forgot_btn ><a style='color:#999' href=?q=pass/recover><img alt='forgot' src=slice/login_all.png/363/761/147/47></a></div>";

    return "$output";
}

function user_form_login() {

    $output = "<table class=menu-block><Tr><Td align=right>".l("Login","user/login")." | ".l("Join","user/register")."</table>";
    return $output;
}

function requires_authorization() {
    if(!isset($_SESSION['userid']) || strlen($_SESSION['userid'])==0) {
	    Header("Location: ?q=user/login&redir=".$_GET['q']);
        die();
    }
}


function page_user_account() {
    global $user;
    $GLOBALS['log'] = "&nbsp";
    if($_POST['submit']) {
        if($_POST['password']) {
            if($_POST['password']!=$_POST['password2']) {
                log_message("Password retype doesn't match");
            } else {
                global $user;
                db_query("UPDATE users SET passmd5='%s' WHERE id=%d LIMIT 1",md5($_POST['password']),$user->id);
                log_message("Password changed.");
            }
        }

        $GLOBALS['log'] = "Information updated";
        db_query("UPDATE users SET comment='%s', gender='%s', country='%s' WHERE id=%d LIMIT 1",$_POST['comment'],$_POST['gender'],$_POST['country'],$user->id);
        $user = database_object_get("users",$user->id);
    }


    if($_POST['logo']) {
        $ext = FileExt(form_upload_file());
        if($ext!='jpg' && $ext!='jpeg' && $ext!='png') {
            log_message("Sorry, only jpg,png images allowed. This one is: $ext.");
        } else {
            $filename = "files/$user->id.$ext";
            if(form_upload_file_put($filename)) {
                log_message("User logo was updated.");
                db_query("UPDATE users SET logo='%s' WHERE id=$user->id",$filename);
                $user->logo = $filename;
            } else {
                log_message("Error occured while uploading user logo");
            };
        }
    }

    form_start("?q=user/account");
    form_output("Email",$user->email);
    form_input("Password","password");
    form_input("Password (retype)","password2");
    form_textarea("Comments","comment",database_fixslashes($user->comment));
    form_input("Gender (budet spisok)","gender",$user->gender);
    form_input("Country (budet spisok)","country",$user->country);
    form_submit("Update information","submit");
    form_end();
    $details = form();
    $details .= "Account balance: $user->account USD &nbsp;&nbsp; | ".l("Deposit Money","user/deposit")." | ".l("View Transactions","account/transactions");

    $output .= "User logo:<br>";
    if($user->logo) {
        $output .= "<img src=$user->logo><br>";
    }
    form_upload("Image","logo");
    $output .= form();

    $logo = $output;

    $GLOBALS['pageheader'] = "My Account";
    $output = "<table><tr><td valign=top><h1>$user->name</h1>$details<td valign=top>$logo</table>";

    return $output;
}

function access_denied() {
    return "<h1>Access Denied</h1><br><br>&nbsp;You are not authorized to view this page.";
}

function cmsurl_encode($s) {
    $s = str_replace("/","(slash)",$s);
    $s = str_replace("?","(q)",$s);
    return $s;
}

function cmsurl_decode($s) {
    $s = str_replace("(slash)","/",$s);
    $s = str_replace("(q)","?",$s);
    return $s;
}

function page_header($header) {
    $GLOBALS['pageheader'] = $header;
}

if(!function_exists("file_put_contents")) {
    function file_put_contents($filename,$s) {
        $f = fopen($filename,"wb");
        fwrite($f,$s);
        fclose($f);
    }
}


function l($caption,$link) {
    global $base_url;
	if(NICE_URLS) 
	   $res = "<a href='$base_url$link'>$caption</a>";
	else
   	   $res = "<a href='$base_url?q=$link'>$caption</a>"; 
    return $res;
}

function fileExt($fileName) {
    return strtolower(substr($fileName, strrpos($fileName, '.') + 1));
}

function page_line($s="") {
    return $s."<br>\r\n";
}

function page_title($s) {
    return page_line("<h1>$s</h1>");
}


function htmlquery($query,$actionstring="") {
    echo htmlquery_code($query,$actionstring);
}

function htmlquery_code($query,$actionstring="") {
    $firstline = true;
    $output = "<table class=table_add>";
    while($rec = db_fetch_array($query)) {
        if($firstline) {
            $output .= "<tr>";
            foreach($rec as $name=>$value) {
                $output .= "<th class=table_add_header>$name";
            }
            if($actionstring)
            $output .= "<th class=table_add_header>";
            $firstline = false;
        }
        $output .= "<tr>";
        foreach($rec as $value) {
            $output .= "<td class=table_add_cell>$value";
        }
        if($actionstring) {
            $actionstring_display = $actionstring;
            foreach($rec as $name=>$value) {
                $actionstring_display = str_replace("[".$name."]",$value,$actionstring_display);
            }
            $output .= "<td class=table_add_cell>$actionstring_display";
        }
    }
    $output .= "</table>";

    @mysql_data_seek($query,0);
    return $output;
}




function menu_by_role($role,$onlythis=false) {
    $output = "";
    if(!$onlythis)
    $menu_items = db_query("SELECT * FROM menu WHERE role1='%s' OR role2='%s' OR role3='%s'
    OR role1='any' OR role2='any' OR role3='any'",$role,$role,$role);

    if($onlythis)
    $menu_items = db_query("SELECT * FROM menu WHERE role1='%s' OR role2='%s' OR role3='%s'",$role,$role,$role);
    while($item = db_fetch_object($menu_items)) {
        if($output) $output .= " | ";
        $output .= l($item->caption,$item->link);
    }

    return $output;
}

function menu_build() {
    global $user;

    $menu = "";

    //not authorized
    if(!$user) {
        $userlogin = user_form_login();
        $menu .= l("Home","home")." | ";
        $menu .= l("About","about")." | ";
        $menu .= l("Sales","sale")." | ";
        $menu .= l("Search","search")." | ";
        $menu .= l("Statistics","stats")." | ";
    }

    //authorized
    if($user) {
        $userlogin .= "<table cellspacing=0 cellpadding=0 width=140><tr><td align=right valign=top>Loged in as $user->name </table>";
        $menu .= l("Home","home")." | ";
        $menu .= l("My Account","user/account")." | ";
        $menu .= l("My Cubes","cubes/view")." | ";
        $menu .= l("New Cube","cube/new")." | ";
        $menu .= l("Sales","sale")." | ";
        $menu .= l("Search","search")." | ";
        $menu .= l("Statistics","stats")." | ";
        $menu .= l("Logout","user/logout");
    }



    $output = "<table width=100%><tr><td width=80% valign=top>$menu<td valign=top>$userlogin</table>";
    return $output;
}



$form = "";

//example:
// form_start("","POST",'enctype="multipart/form-data"');
// form_input("value","value");
// form_submit("submit","submit");
// echo form();

function form_start($link="",$method="POST", $additional="") {
    global $form;
    $form = "<form style='padding:20px' action=\"$link\" method=\"$method\" $additional>
       <table class=form_edit>\r\n";
    if(strtolower($method)=='get') {
        $form .= "<input type=hidden name=q value=$_GET[q]>";
    }
}

function form_file($caption,$name) {
    global $form;
    $form .= "<tr><td class=form-caption>$caption:
    <td>
    <input name='$name' type='file'>
    <input type='hidden' name='MAX_FILE_SIZE' value='100000000' >
    <br>";

}

function form_hidden($name,$value) {
  global $form;
  $form .= "<input type='hidden' name='$name' value='$value'>";
}

function form_upload($caption,$name) {
    global $form;
    $form = "<form enctype='multipart/form-data'  method='POST'>
    <input type='hidden' name='MAX_FILE_SIZE' value='100000000' >
    $caption:
    <input name='uploadedfile' type='file'>
    <input type=submit name='$name' value='{~Upload File}'>
    </form>";
}

function form_file_uploaded($fname) {
    if(!isset($_FILES[$fname]['name'])) return false;
    return $_FILES[$fname]['name']<>"";
}

function form_file_uploaded_move($fname,$path) {
    assert(form_file_uploaded($fname));
    return move_uploaded_file($_FILES[$fname]['tmp_name'],$path);
}


function form_upload_file() {
    if(!isset($_FILES['uploadedfile'])) return false;
    return basename($_FILES['uploadedfile']['name']);
}

function form_upload_file_put($filename) {
    if(!$_FILES['uploadedfile']['tmp_name']) {
        die("FILE WASN'T UPLOADED. upload_max_filesize in php.ini: ".ini_get("upload_max_filesize"));
    }
    return move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$filename);
}

function form_header($caption) {
    global $form;
    $form .= "<h1>$caption</h1><br>\r\n";
}

function form_label($caption) {
    global $form;
    $form .= "<tr><td><td>$caption<br>\r\n";
}
function form_password($caption,$name,$value="",$size="",$attributes="") {
  global $form;
  if($size) $size = "size=$size";
  if($attributes) $attributes = " $attributes ";
  $form .= "<tr><td>$caption:<td><input type=password name='$name' value='$value' $size$attributes>\r\n";
}

function form_timestamp($caption,$name,$value="") {
  global $form;
  if($value=="") $value = time();
  $year = @date("Y",$value);
  $month = @date("m",$value);
  $day = @date("d",$value);
  
  $hour = @date("H",$value);
  $min = @date("i",$value);
  $sec = @date("s",$value);
  $form .= "<tr><td>$caption (Y/M/D H:M:S)<td>
  
    year<input name=".$name."_year value='$year' size=2>
    month<input name=".$name."_month value='$month' size=1>
    day<input name=".$name."_day value='$day' size=1>
    -
    hour<input name=".$name."_hour value='$hour' size=1>
    min<input name=".$name."_min value='$min' size=1>
    sec<input name=".$name."_sec value='$sec' size=1>
  ";
   return @date("Y/m/d H:i:s",$time);    
}

function form_input($caption,$name,$value="",$size="",$attributes="") {
    global $form;
    if($size) $size = "size=$size";
	if($attributes) $attributes = " $attributes ";
    $form .= "<tr><td class=form-caption>$caption:<td><input name='$name' value='$value'$size$attributes>\r\n";
}

function form_output($caption,$value) {
    global $form;
    $form .= "<tr><td>$caption:<td>$value\r\n";

}

function form_textarea($caption,$name="",$value="",$cols=40,$rows=4) {
    global $form;
    if($caption) $caption="$caption:";
    $form .= "<tr><td valign=top class=form-caption>$caption<td><textarea name='$name' cols=$cols rows=$rows>$value</textarea>";
}


function form_list($caption,$name,$key_value_list,$key_value="",$size=40,$attributes="") {
    global $form;
    $list = "<select name='$name' $attributes >";
    foreach($key_value_list as $key=>$value) {
        $selected = "";
        if($key==$key_value) $selected = " selected ";
        $list .= "<option value=$key $selected>$value";
    }
    $list .= "</select>";

    $form .= "<tr><td class=form-caption>$caption:<td>$list";

}
function form_checkbox($caption,$name,$checked_bool=0,$value="") {
    global $form;
    $checked = "";
    if($checked_bool) $checked = " checked";
    if(!$value) $value = $name;

    $form .= "<tr><td class=form-caption>$caption<td><input type='checkbox' name='$name' value='$value' $checked>";
}

function form_submit($caption,$name, $attributes="") {
    global $form;
	if($attributes) $attributes = " $attributes ";
    $form .= "<tr><td><td><input type=submit name='$name' value='$caption'$attributes>";
}
function form_end() {
    global $form;
    $form .= "</table></form>\r\n";
}


function form_flat() {
    global $form;
    $f = $form;
    $f = str_replace("<br>","",$f);
    $f = str_replace("<td>","",$f);
    $f = str_replace("<tr>","",$f);
    $f = str_replace("<table>","",$f);
    $f = str_replace("</table>","",$f);
    return $f;
}

function form() {
    global $form;
    return $form;
}

function form_post($name) {
    if(isset($_REQUEST[$name])) {
        return $_REQUEST[$name];
    } else {
        return null;
    }
}

//print_r with <pre></pre>
function pr($value) {
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}


$ln_margin = 0;
//*
//*  margin increase
//*

function ln_inc($value=1) {
  global $ln_margin;
  $ln_margin +=$value;
}


//*
//* margin decrease
//*

function ln_dec($value=1) {
  global $ln_margin;
  $ln_margin -= $value;
  
}


$ln_enabled = 1;

function ln_disable() {
  global $ln_enabled;
  $ln_enabled = 0;
}

function ln_enable() {
  global $ln_enabled;
  $ln_enabled = 1;
}

function inbrowser() {
   return  !(@$_SERVER['CLIENTNAME']=='Console' || @$_SERVER['SESSIONNAME']=='Console');
}
//*
//* show line
//* 

$ln_margin_step = 2;
function ln_margin_step($v) {
  global $ln_margin_step;
  $ln_margin_step = $v;
}
function ln($s) {
    global $ln_enabled;
    if(!$ln_enabled) return;
    global $ln_margin;
    global $ln_margin_step;
    if(!inbrowser()) {
        $ln_margin_s = str_repeat(str_repeat(" ",$ln_margin_step),$ln_margin);
        echo $ln_margin_s."$s\r\n";
    } else {
        $ln_margin_s = str_repeat(str_repeat("&nbsp;",$ln_margin_step),$ln_margin);
        echo $ln_margin_s."$s<br>";
    }
}



function db_last_id() {
    if(mysql)
    return mysql_insert_id();
    if(sqlite2) {
        global $dbhandle;
        return sqlite_last_insert_rowid($dbhandle);
    }
}


function redir($method) {
	global $base_url;
	if(NICE_URLS)
	  Header("Location: $base_url$method");
	else
      Header("Location: ?q=$method");
    die();
}

//requires table static (id,name,value)
function static_set($variable, $value) {
    $id = db_result(db_query("SELECT id FROM static WHERE name='%s'",$variable));
    if(!$id) {
        db_query("INSERT INTO static (name) VALUES ('%s')",$variable);
        $id = db_last_id($id);
    }
    db_query("UPDATE static SET value='%s' WHERE id=%d",$id);
}

//requires table static (id,name,value)
function static_get($variable, $default="") {
    $id = db_result(db_query("SELECT id FROM static WHERE name='%s'",$variable));
    if(!$id) return $default;
    else {
        $value = db_result(db_query("SELECT value FROM static WHERE id=%d",$id));
        return $value;
    }
}

function table_start($colcount) {
    global $table;
    global $table_i;
    global $table_cols;
    $table_cols = $colcount;
    $table_i = 0;
    $table = "";
}

function table_add($value,$attributes="") {
    global $table;
    global $table_i;
    global $table_cols;
	global $table_row_attributes;
	if(!isset($table_row_attributes)) $table_row_attributes = "";
    if($table_i % $table_cols==0) {
	    if($table_row_attributes)  {
          $table .= "<tr class=table_row $table_row_attributes >";
		} else
          $table .= "<tr class=table_row>";
    }
    if(strlen($attributes)==0)
      $attributes = "class=table_add_cell";
    $table .= "<td $attributes >$value";
    $table_i++;
}

function table_add_header($value,$attributes="") {
    global $table;
    global $table_i;
    global $table_cols;
    if($table_i % $table_cols==0) {
        $table .= "<tr>";
    }
    if(strlen($attributes)==0)
      $attributes = "class=table_add_header";
    $table .= "<th $attributes >$value";
    $table_i++;
}

function table($attributes="") {
  return table_flush($attributes);
}

function table_flush($attributes="") {
    global $table;
    if(!$attributes)
      $attributes = "class=table_add";
    return "<table $attributes >$table</table>";
}

function dynthumb($width, $link) {
    $newname = "thumbs/".md5($link.$width).".jpg";
    if(file_exists($newname)) {
        return $newname;
    }
    else return "dynthumb.php?width=$width&file=$link";
}

//generates links 1..10
//$href/1 , $href/2, $href/3

function paginator($href, $page) {
  $s = "";
  if($page>1)
    $s .= "<a href=$href".($page-1)."><- prev</a>";
  $start = $page-5;
  if($start<1) $start = 1;
  for($i = $start;$i<$start+5;$i++) {
     if($i==$page)
       $s .= " $page ";
     else
       $s .= " <a href=$href$i>$i</a> ";
  
  }
  
  $s .= "<a href=$href".($page+1).">next -> </a>";
  return $s;
}


function form_object_edit($tablename,$id,$fields,$captions="",$submitcaption="submit") {
  page_header("$tablename: edit");
  $ff = split(",",$fields);
  $cc = split(",",$captions);
  
  if(form_post("submit")) {
    $set = "";
    foreach($ff as $f) {
      if($set)
        $set .= ",";
      $set .= "$f='%s'";
    }
    
    assert(is_numeric($id));
    assert(count($ff)<=10);
    db_query("UPDATE $tablename SET $set WHERE id=$id",
             empty($ff[0])?"":form_post($ff[0]),
             empty($ff[1])?"":form_post($ff[1]),
             empty($ff[2])?"":form_post($ff[2]),
             empty($ff[3])?"":form_post($ff[3]),
             empty($ff[4])?"":form_post($ff[4]),
             empty($ff[5])?"":form_post($ff[5]),
             empty($ff[6])?"":form_post($ff[6]),
             empty($ff[7])?"":form_post($ff[7]),
             empty($ff[8])?"":form_post($ff[8]),
             empty($ff[9])?"":form_post($ff[9]));
  }
  
  $r = db_object_get($tablename,$id);
  form_start();
  foreach($ff as $i=>$f) {
    form_input(empty($cc[$i])?$f:$cc[$i],$f,$r->$f);
  }
  form_submit($submitcaption,"submit");
  form_end();

  return form();    
    
}

/*
  $form = form_object_add("videos","title,genre,publisher,producer");
  if(!$form) {
    redir("edit/".db_last_id());
  } else {
    return $form;
  }
*/


function form_object_add($tablename,$fields,$captions="",$submitcaption="Submit") {
   page_header("$tablename: add");
   if(form_post("submit")) {
      $ff = split(',',$fields);
      $values = "";
      foreach($ff as $f) {
        $values .= ",'%s'";
      }
      db_query("INSERT INTO $tablename (id,$fields) VALUES (null $values)",
               form_post(empty($ff[0])?"":$ff[0]),
               form_post(empty($ff[1])?"":$ff[1]),
               form_post(empty($ff[2])?"":$ff[2]),
               form_post(empty($ff[3])?"":$ff[3]),
               form_post(empty($ff[4])?"":$ff[4]),
               form_post(empty($ff[5])?"":$ff[5]),
               form_post(empty($ff[6])?"":$ff[6]),
               form_post(empty($ff[7])?"":$ff[7]),
               form_post(empty($ff[8])?"":$ff[8]),
               form_post(empty($ff[9])?"":$ff[9]),
               form_post(empty($ff[10])?"":$ff[10]));
      assert(count($ff)<=10);
      $id = db_last_id();
      return "";
   }
   
   form_start();
   $fields = split(",",$fields);
   $captions = split(",",$captions);
   foreach($fields as $i=>$field) {
     $caption = $field;
     if(!empty($captions[$i])) {
        $caption = $captions[$i];
     }
     form_input($caption,$field);
   }
   form_submit($submitcaption,"submit");
   form_end();
   return  form();
}

function db_object_del($tablename,$id) {
    db_query("DELETE FROM $tablename WHERE id=%d",$id);
}


function MSG($message) {
  $s = date("H:m:s",time())." ".$_SERVER['SCRIPT_NAME']." MESSAGE: $message\r\n";
  $f = @fopen("trace","a");
  if($f) {
    fwrite($f,$s);
    fclose($f);
  }

}

function EXCEPTION($message) {
  $s = date("H:m:s",time())." ".$_SERVER['SCRIPT_NAME']." EXCEPTION: $message\r\n";
  $f = @fopen("trace","a");
  if($f) {
    fwrite($f,$s);
    fclose($f);
  }
  die();
}

function TRACE($var,$comment="") {
  $s = print_r($var,true);

  if(filesize("trace")>1024*100) {
    file_put_contents("trace","");
  }
  
  $f = @fopen("trace","a");
  if($f) {
    if($comment) $comment = " ".$comment;
    $s = date("H:m:s",time())." ".$_SERVER['SCRIPT_NAME']." $comment\r\n".$s."\r\n";
    fwrite($f,$s);
    fclose($f);
  }
  
}



/*

function table_edit($tablename,$home,$action,$id="",$masterfield="",$mastervalue="", $order="",$actionstring) {

$tablename - table name from global $tables
$home - home link (value after ?q=)
$action - action parameter passed to link
$id - id parameter passed to link
$masterfield - for master fields
$mastervalue - for master value
$order - value for ORDER BY 
$actionstring - example: <a href=preview/[ID]>preview</a>
$actionstring - may be function name, id is passed to it

Universal Table Editor


USES: global $tables;
$tables[] = "playlists";
$tables[] = "videos";

$tables["videos"]["fields"][] = "title";
$tables["playlists"]["fields"][] = "title";

$tables["main"]["fields"][] = "time_hour";
$tables["main"]["fields"][] = "time_min";
$tables["main"]["fields"][] = "playlist_id";

$tables["playlist_videos"]["fields"][] = "video_id";
$tables["playlist_videos"]["weight"] = 1;


USAGE: table_edit("playlist","playlist",$action,$id,"parent_id","2");

EXAMPLE 1: 
function page_playlists($action,$id="") {
  return table_edit("playlists","playlists",$action,$id);
}


EXAMPLE 2:
function page_mainlist_day($dayid, $action,$id="") {
  $s = table_edit('main','mainlist/day/'.$dayid, $action, $id, "day_id", $dayid, "time_hour, time_min");

  $d = db_object_get("days",$dayid);
  page_header("Main Playlist - ".$d->title);
  return $s;
}

*/

function str_end($value,$end) {
  return strtolower(substr($value,strlen($value)-strlen($end))) == strtolower($end);
}

function str_start($value,$end) {
  return substr($value,0,strlen($value)-strlen($end));
}

function str_prefix($s) {
  $s = explode("_",$s);
  return $s[0]."_";
}

function table_long_alias($table) {
  global $tables;
                if(isset($tables[$table]['long_alias'])) return $tables[$table]['long_alias'];
                else return $table;
}

class ctable_edit_props {
  public $new_record_show = true;
  public $new_record_html = "";
  public $action_string_left = "";
  public $del_record_show = true;
  public $edit_record_show = true;
  public $col_title_show = true;
  public $add_record_html = "";
  public $add_redir = true;
  public $add_records = true;
  public $add_record_button_show = true;
}

$table_edit_props = new ctable_edit_props();

function table_edit($tablename,$home="",$action="",$id="",$masterfield="",$mastervalue="", $order="", $actionstring_or_function="") {
  
    if(!$home) $home = self_q();
    if(!$action) $action = arg(0);
    if(!$id) $id = arg(1);

	global $table_edit_props;

    $actionstring = $actionstring_or_function;
    ////////////// PREPARE
    $table_long_alias = $tablename;
    global $tables;
    
    if(isset($tables[$tablename]['weight']))
      weight_fix($tablename);
	
    $table_long_alias = table_long_alias($tablename);
    /////////////////////////////////////

    $master_cond = "";
    if($masterfield) {
      if(strtolower($mastervalue)=='null')
		  $master_cond = " AND $masterfield is null";
	  else
          $master_cond =  " AND $masterfield='$mastervalue' ";
	}
    
    global $tables;
    
    if(!isset($tables[$tablename])) {
      die("error, table_edit - tables[$tablename] not set");
    }
    
    if(isset($tables[$tablename]['weight'])) {
        if($order) $order .= ",";
        $order .= " $tablename.weight ";
    }
    
    if($order) {
        $order = " ORDER BY $order ";
    }
    

    //this is reaction on drag and drop reorder
	if($action=="move") {
      $d = $_REQUEST['delta'];
	  if($d>0) {
		for($i=0;$i<$d;$i++) {
          table_edit($tablename,"return!","down",$id,$masterfield,$mastervalue);
		}
	  }
	  if($d<0) {
		$d = -$d;
		for($i=0;$i<$d;$i++) 
          table_edit($tablename,"return!","up",$id,$masterfield,$mastervalue);
	  }
	  die("");
	}

    if($action=="up") {
       $weight = db_result(db_query("SELECT weight FROM $tablename WHERE id=%d $master_cond",$id));
       $prevweight = db_result(db_query("SELECT max(weight) FROM $tablename WHERE weight<%f $master_cond",$weight));
       $previd = db_result(db_query("SELECT id FROM $tablename WHERE weight=%f $master_cond",$prevweight));

       db_query("UPDATE $tablename SET weight=%f WHERE id=%d $master_cond",$prevweight,$id);
       db_query("UPDATE $tablename SET weight=%f WHERE id=%d $master_cond",$weight,$previd);
	   if($home=='return!') return;
	   redir($home);            
    }
    
    if($action=="down") {
        $weight = db_result(db_query("SELECT weight FROM $tablename WHERE id=%d $master_cond",$id));
        $prevweight = db_result(db_query("SELECT min(weight) FROM $tablename WHERE weight>%f $master_cond",$weight));
        if($prevweight) {
          $previd = db_result(db_query("SELECT id FROM $tablename WHERE weight=%f $master_cond",$prevweight));

          db_query("UPDATE $tablename SET weight=%f WHERE id=%d $master_cond",$prevweight,$id);
          db_query("UPDATE $tablename SET weight=%f WHERE id=%d $master_cond",$weight,$previd);
        }
	    if($home=='return!') return;
        redir($home);    
    }
    
    if($action=="del") {
        db_query("DELETE FROM $tablename WHERE id=%d $master_cond",$id);
    }
    if($action=="edit") {
        
        if(form_post("edit")) {
            
            $sets = "";
            foreach($tables[$tablename]['fields'] as $value) {
                if($sets)
                  $sets .= ", ";

                if(str_end($value,"_check")) {
				   if(form_post($value)) 
				     $sets .= "$value=1";
				   else
					 $sets .= "$value=0";
				} else
                if(str_end($value,"_time")) {
                  //hms mdy
                    $f = str_start($value,"_time");
                    $ts = mktime(form_post($f."_hour"),
                          form_post($f."_min"),
                          form_post($f."_sec"),
                          form_post($f."_month"),
                          form_post($f."_day"),
                          form_post($f."_year"));
                   $sets .= "$value=$ts";
                   
                } else {
                  $p = form_post($value);

				  $p = SlashSymbolsFix($p);
                  if(mysql)
                       $p = mysql_real_escape_string($p);
                  if(sqlite2)
                       $p = sqlite_escape_string($p);
                  if($p=="null") 
                    $sets .= "$value=null";
                  else
                    $sets .= "$value = '".$p."' ";
                }
            }
            
            
            $s = "UPDATE $tablename SET $sets WHERE id=$id $master_cond";
            db_query($s);
            $callback = "table_".$tablename."_edit";
            if(function_exists($callback)) {
              $callback($id);
            }
            redir($home);
        }
        
        page_header("Edit $table_long_alias");
        $r = db_object_get($tablename,$id);
        form_start();

		table_edit_form_generate($tablename,$r);
        
        form_submit("Save changes","edit");
        form_end();
        
        return form();
        
    }
    
    if($action=="add" && $table_edit_props->add_records) {
        if(form_post("add")) {
            //fixme: unsecure, sql injection
            $fields = "";
            $values = "";
            foreach($tables[$tablename]['fields'] as $field) {
              if($fields) $fields .= ", ";
              $fields .= $field;
              if($values) $values .= ", ";

			  if(str_end($field,"_check")) {
				   if(form_post($field)) 
				     $values .= "1";
				   else
					 $values .= "0";
			  } else
              if(str_end($field,"_time")) {
                  //hms mdy
                    $f = str_start($field,"_time");
                    $ts = mktime(form_post($f."_hour"),
                          form_post($f."_min"),
                          form_post($f."_sec"),
                          form_post($f."_month"),
                          form_post($f."_day"),
                          form_post($f."_year"));
                   $values .= "$ts";              
              } else {
                $p = form_post($field);
				$p = SlashSymbolsFix($p);
                if(mysql)
                     $p = mysql_real_escape_string($p);
                if(sqlite2)
                     $p = sqlite_escape_string($p);

                if($p == 'null')
                  $values .= "null";
                else
                  $values .= "'".$p."'";
              }
            }
            
            if($masterfield) {
                $fields .= ", $masterfield";
				if(strtolower($mastervalue)=='null')
					$values .= ", null ";
				else
                    $values .= ", '$mastervalue'";
            }
            
            if(isset($tables[$tablename]['weight'])) {
                $fields .= ", weight";
                $values .= ", ".(db_result(db_query("SELECT max(id) FROM $tablename"))+1);
            }
            db_query("INSERT INTO $tablename (id, $fields) VALUES (null, $values)");
            $id = db_last_id();
            $callback = "table_".$tablename."_edit";
            if(function_exists($callback)) {
              $callback($id);
            }
            if($table_edit_props->add_redir)
               redir($home);
        }
        page_header("Add $table_long_alias");
        form_start("","post"," name=add_form ");
        
		table_edit_form_generate($tablename);

        if($table_edit_props->add_record_html) {
          global $form;
          $form .= "<tr><td><td>".$table_edit_props->add_record_html;
        }
        
        if($table_edit_props->add_record_button_show) {
          form_submit("Add record","add");
        } else {
          form_hidden("add","1");
        }
        form_end();
        
        return form();
        
        
    }
    
    if(strlen($GLOBALS['pageheader']==0)) {
      if(!str_end($table_long_alias,"s")) {
        page_header("$table_long_alias"."s List");
      } else {
        page_header($table_long_alias);
      }
    }
    
      
    $ff = $tables[$tablename]['fields'];
    $fields = "";
    $joins = "$tablename";
    $titles = array();
    foreach($ff as $f) {
        if($fields) $fields .= ", ";
        $type = substr($f,strlen($f)-3,3);
                               
        if($type == "_id") {
            
           $cap = substr($f,0,strlen($f)-3);
           $table = $cap."s";
           if(!isset($tables[$table])) {
              $table = str_prefix($tablename).$table;
           }
           
           $titlefield = "";
           foreach($tables[$table]['fields'] as $v) {
             $titlefield = $v;
             break;
           }
           $fields .= " $table.$titlefield as $cap ";
           $joins .= " LEFT JOIN $table ON $tablename.$f = $table.id ";
           $titles[] = $cap;
           
        } else {
           $fields .= "$tablename.$f";
           $titles[] = $f;
        }
    }

    
    $where = "";
    if($masterfield) {
	  if(strtolower($mastervalue)=='null') {
		  $where = " WHERE $masterfield is null ";
	  } else {
          $where = " WHERE $masterfield='$mastervalue' ";
	  }
    }
    
    $q = "SELECT $tablename.id as id, $fields FROM $joins $where $order";

   $act = "";
   if($table_edit_props->edit_record_show) {
     $act .= "<a href=?q=$home/edit/[id]><img src=images/edit.png border=0></a>";
   }
   if($table_edit_props->del_record_show) {
     $act .= "<a href=?q=$home/del/[id]><img onclick=\"return confirm('Are you sure?');\"src=images/del.png border=0></a>";
   }
    
    if(isset($tables[$tablename]['weight'])) {
        $act = " <a href=?q=$home/up/[id]><img src=images/up.png></a> <a href=?q=$home/down/[id]><img src=images/down.png></a> ".$act;
    }
    
    $rr = db_query($q);
    
    $s = "";

    if(!db_num_rows($rr)) {
      $s .= "{~no records}<br>";
      
    } else {
      if($table_edit_props->action_string_left) {
        table_start(count($ff)+2);
        if($table_edit_props->col_title_show) 
          table_add(""," class=table_edit_header ");
      } else 
        table_start(count($ff)+1);

		///HEADERS
      if($table_edit_props->col_title_show)  {
        foreach($titles as $v) {
		  if(str_end($v,"_check")) {
			$v = str_start($v,"_check");
		  } else
          if(str_end($v,"_text")) {
            $v = str_start($v,"_text");
          }
          table_add("{~$v}", " class=table_edit_header ");
        }

        table_add(""," class=table_edit_header ");
      }

      while($r = db_fetch_array($rr)) {

		////// table add id attribute to tr
	    global $table_row_attributes;
        $table_row_attributes = " id=".$r['id']." ";
		///////////////////////////////////////

        $acts_left = "";
        if($table_edit_props->action_string_left) {
          $acts_left = $table_edit_props->action_string_left;
          $acts_left = str_replace("[id]",$r['id'],$acts_left);
          table_add($acts_left);
        }

        foreach($r as $key=>$value) {
		  if(str_end($key,"_check")) {
			if($value==1) {
              table_add("<INPUT TYPE=CHECKBOX READONLY readonly='readonly' checked onclick='javascript:return false'>");
			} else {
              table_add("<INPUT TYPE=CHECKBOX READONLY readonly='readonly' onclick='javascript:return false'>");
			}
		  } else
          if(str_end($key,"_time")) {
            table_add( @date("Y/m/d H:i:s",$value));
          } else
          if($key!='id')
          table_add($value," class=table_edit_cell ");
        }
        
        $useract = "";
        if(function_exists($actionstring)) {
          $useract = $actionstring($r['id']);
        } else {
          $useract = $actionstring;
        }
        
        $acts = str_replace("[id]",$r['id'],$act." ".$useract);
        
        table_add($acts);
      }
    
      $s = "";
      $s .= table_flush(" class=table_edit ");

	  /// table drag reorder
	  if(isset($tables[$tablename]['weight']) && $tables[$tablename]['weight'])
	    $s .= table_edit_drag_code($home);
	  /////

    }
    if($table_edit_props->new_record_show && $table_edit_props->add_records) {
       $html = "<img src=images/add.png border=0>&nbsp;{~Add a new record}";
       if($table_edit_props->new_record_html) $html = $table_edit_props->new_record_html;
       $s .= "<br><a href=?q=$home/add>$html</a>";
    }

    return $s;
    
}

function table_edit_form_generate($tablename,$r="") {
	global $tables;

        //this is for add
        if(!$r) { $r = new stdClass(); }

		foreach($tables[$tablename]["fields"] as $value) {
			//this is for add
			if(!isset($r->$value)) $r->$value = "";

			if( str_end($value,"_check") ) {
              form_checkbox("{~".str_start($value,"_check")."}:",$value,$r->$value);
			} else
            if( str_end($value,"_time") ) {
               $shortname = str_start($value,"_time");
               form_timestamp($shortname,$shortname,$r->$value);
            } else
            if( str_end($value,"_id") ) {
                $cap = substr($value,0,strlen($value)-3);
                $table = $cap."s";
                if(!isset($tables[$table])) $table = str_prefix($tablename).$table;
                
                $fields = "id";
                
                //use first field as list value
                foreach($tables[$table]['fields'] as $list_value) {
                    $fields .= ", $list_value";
                    break;
                }
                $list = db_list(db_query("SELECT $fields FROM $table"));
                $table = table_long_alias($table);                
                form_list($table,$value,$list,$r->$value);
            } else {
//              $r->$value = htmlentities($r->$value,ENT_QUOTES);
              if(str_end($value,"_text"))
                form_textarea("{~".str_start($value,"_text")."}",$value,$r->$value);
              else
                form_input("{~$value}",$value,$r->$value);
            }
        }
}

function table_edit_drag_code($home) {
  $o = "
<script src='uses/jquery.js'></script>
<script src='uses/ui/jquery.ui.core.js'></script>
<script src='uses/ui/jquery.ui.widget.js'></script>
<script src='uses/ui/jquery.ui.mouse.js'></script>
<script src='uses/ui/jquery.ui.sortable.js'></script>
    <script>
	$(function() {
    var original_index = 0;
    function list_start(e,ui) {
		original_index = $('.table_edit tbody tr:[id]').index(ui.item);
		var id = ui.item.attr('id');
		if(!id) return false;
    }
    function list_update(e,ui) {
		if($('.table_edit tbody tr').index($('.tr_headers')) > 0) return false;
		var id = ui.item.attr('id');
		var new_index = $('.table_edit tbody tr:[id]').index(ui.item);
        var d = new_index - original_index;
		var move_url = '?q={$home}/move/'+id+'&delta='+d;
		$.get(move_url);
    }

    $('.table_edit tr .table_edit_header').parent().addClass('tr_headers');
	$('.table_edit tbody').sortable( { cancel: '.tr_headers',
	  update: list_update,
	  start: list_start
	} );
});
  </script>";
  return $o;
}


function db_list($rr) {
    $res = array();
    $res['null'] = '---';
    while($r = db_fetch_array($rr)) {
      $id = "";
      $value = "";
      foreach($r as $key=>$keyvalue) {
        if($key=="id") {
            $id = $keyvalue;
        } else {
            $value = $keyvalue;
        }
      }
      $res[$id] = $value;
    }
    return $res;
}


function SlashSymbolsFix($s) {
    $s = str_replace("\\\\","\\",$s);
    $s = str_replace('\"','"',$s);
    $s = str_replace("\'","'",$s);
	return $s;
}

function HTTPPost($s) {
  if(HTTP_POST_DOUBLE_SLASH) {
    $s = str_replace("\\\\","\\",$s);
    return $s;
  } else return $s;
}

function HTTPPostFix() {
   global $HTTPPostFix_Called;
   if($HTTPPostFix_Called) return;
   $HTTPPostFix_Called = 1;

   if(!HTTP_POST_DOUBLE_SLASH) return;

   foreach($_REQUEST as &$v) {
     $v = HTTPPost($v); 
   }

   foreach($_GET as &$v) {
     $v = HTTPPost($v); 
   }

   foreach($_POST as &$v) {
     $v = HTTPPost($v); 
   }

}


function str_itest($s1,$s2) {
    $s1 = strtolower($s1);
    $s2 = strtolower($s2);
    return str_test($s1,$s2);    
}

function test_ok() {
  if(inbrowser()) {
    return "<font color=green>OK</font>: ";
  } else {
    return "OK: ";
  }
}
function test_failed() {
  if(inbrowser()) {
     return "<font color=red>FAILED</font>: ";    
  } else {
    return "FAILED: ";
  }
}
function str_test($s1,$s2) {
    if($s1!=$s2) {
      ln(test_failed()."\"$s2\" expected, got \"$s1\"");
      return false;
    } else {
      ln(test_ok()."$s1");
    }
    return true;
    
}

function test_not_equal($s1,$s2) {
  if($s1==$s2) {
    ln(test_failed()."'$s1' equal to '$s2', expected not equal");
  } else {
    ln(test_ok()."'$s1' not equal to '$s2'");
  }
  
}
function test($v1,$v2) {
  str_test($v1,$v2);
}

function db_connect() {

  if(mysql) {
    mysql_pconnect(MYSQL_HOST,MYSQL_USER,MYSQL_PASS);
    mysql_selectdb(MYSQL_DB);
  }

  if(sqlite2) {
    global $dbhandle;
    $dbhandle = sqlite_open(SQLITE2_DB);
    if(!$dbhandle) ln("can't connect to sqlite database");
  }
}

function db_exists($res) {
  $numrows = db_num_rows($res);
  
  return ($numrows>0); 
}


function TimeSpan2Str($t) {
   $min = ($t / 60) % 60;
   $hour = ($t /(60*60));
   return sprintf("%d:%02d:%02ds",$hour,$min,$t%60);
}

$db_table_last = "";
$db_data_model_create = false;

function db_table_drop($tablename) {
  global $db_data_model_create;
  if($db_data_model_create) {
    sql("DROP TABLE $tablename");
  }
}
function db_table($tablename,$fields = "",$long_alias="") {
  global $db_table_last;
  global $db_data_model_create;
  $db_table_last = $tablename;

  if($db_data_model_create) {
    if(mysql)
    sql("
      CREATE TABLE `$tablename` (
        `id` int(11) NOT NULL auto_increment,
        PRIMARY KEY  (`id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8");
    else
    if(sqlite2)
    sql("CREATE TABLE $tablename (id INTEGER  PRIMARY KEY NOT NULL)");
    
  } else {
    global $tables;
    if($long_alias == "") $long_alias = $tablename;
    $tables[$tablename]['long_alias'] = $long_alias;
  }

  if(strlen($fields)) {
      $fields = explode(",",$fields);
      foreach($fields as $field) {
        db_field(trim($field));
      }
  }
}


function db_field($fields,$editable=true) {
  $fields = explode(",",$fields);
  global $db_data_model_create;

  foreach($fields as $field) {
    global $db_table_last;

    if($db_data_model_create) {
      if(str_end($field,"_time")) {
        $field = "$field INT(10)";
      }

      if(str_end($field,"_id")) {
        $field = "$field INT(11)";
      }

      sql("ALTER TABLE $db_table_last ADD $field");
    } else {
          $field = explode(" ",$field);
          $field = $field[0];
          global $tables;
          if($editable) {
            $tables[$db_table_last]['fields'][] = $field;
          } else {
            $tables[$db_table_last]['fields_hidden'][] = $field;
          }
    }
  }
}



function db_data_model_create() {
  
  global $db_data_model_create;
  $db_data_model_create = true;

  global $db_query_error_function;
  $db_query_error_function = "ln";

}


function session_url_save() {
   session_return_url_save();
}

function session_return_url_save() {
  $_SESSION['return_url'] = $_GET['q'];
  if(isset($_SESSION['session_return_message'])) {
    $msg =  $_SESSION['session_return_message'];
    $_SESSION['session_return_message'] = "";
    if($msg)
      log_message($msg);
  }

}

function session_return($message = "") {
  $return_url = $_SESSION['return_url'];
  $_SESSION['return_url'] = "";
  $_SESSION['session_return_message'] = $message;
  redir($return_url);
}


function session_return_page() {
  session_return();
  die();
}


/////////////////////// MENUS

$menus = array();

function menu($menuname) {
 global $menu_last;
 $menu_last = $menuname;
}

function menu_item($caption="",$link="") {
 global $menu_last;
 global $menus;
 $menu_item = new stdClass();
 $menu_item->link = $link;
 if(strlen($caption)==0) $caption = $link;
 $menu_item->caption = $caption;
 $menus[$menu_last][] = $menu_item;
}


function menu_links($menuname) {
  global $menus;
  $o = "";
  if(isset($menus[$menuname])) {
    foreach($menus[$menuname] as $menu_item) {

      $o .= "<a href=?q=$menu_item->link>$menu_item->caption</a><br>";

    }
  }
  return $o;
}


////////////////////////////////// DATAMODEL
function dm_page() {
   db_data_model_create();
   data_model(true);
   die("done");
}

function time2str($time) {
  return @date("Y/m/d H:i:s",$time);
}


function mysql_dump() {

/**
 * Dump data from MySQL database
 *
 * @name    MySQLDump
 * @author  Marcus Vinнcius <mv@cidademais.com.br>
 * @version 1.1 2005-06-01
 * @example
 *
 * $dump = new MySQLDump();
 * print $dump->dumpDatabase("mydb");
 *
 */
class MySQLDump {


	/**
	 * Dump data and structure from MySQL database
	 *
	 * @param string $database
	 * @return string
	 */
	function dumpDatabase($database) {

		// Set content-type and charset
		header ('Content-Type: text/html; charset=iso-8859-1');

		// Connect to database
		$db = @mysql_select_db($database);

		if (!empty($db)) {

			// Get all table names from database
			$c = 0;
			$result = mysql_list_tables($database);
			for($x = 0; $x < mysql_num_rows($result); $x++) {
				$table = mysql_tablename($result, $x);
				if (!empty($table)) {
					$arr_tables[$c] = mysql_tablename($result, $x);
					$c++;
				}
			}

			// List tables
			$dump = '';
			for ($y = 0; $y < count($arr_tables); $y++){

				// DB Table name
				$table = $arr_tables[$y];

				// Structure Header
				$structure .= "-- \n";
				$structure .= "-- Table structure for table `{$table}` \n";
				$structure .= "-- \n\n";

				// Dump Structure
				$structure .= "DROP TABLE IF EXISTS `{$table}`; \n";
				$structure .= "CREATE TABLE `{$table}` (\n";
				$result = mysql_db_query($database, "SHOW FIELDS FROM `{$table}`");
				while($row = mysql_fetch_object($result)) {

					$structure .= "  `{$row->Field}` {$row->Type}";
					$structure .= (!empty($row->Default)) ? " DEFAULT '{$row->Default}'" : false;
					$structure .= ($row->Null != "YES") ? " NOT NULL" : false;
					$structure .= (!empty($row->Extra)) ? " {$row->Extra}" : false;
					$structure .= ",\n";

				}

				$structure = ereg_replace(",\n$", "", $structure);

				// Save all Column Indexes in array
				unset($index);
				$result = mysql_db_query($database, "SHOW KEYS FROM `{$table}`");
				while($row = mysql_fetch_object($result)) {

					if (($row->Key_name == 'PRIMARY') AND ($row->Index_type == 'BTREE')) {
						$index['PRIMARY'][$row->Key_name] = $row->Column_name;
					}

					if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '0') AND ($row->Index_type == 'BTREE')) {
						$index['UNIQUE'][$row->Key_name] = $row->Column_name;
					}

					if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '1') AND ($row->Index_type == 'BTREE')) {
						$index['INDEX'][$row->Key_name] = $row->Column_name;
					}

					if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '1') AND ($row->Index_type == 'FULLTEXT')) {
						$index['FULLTEXT'][$row->Key_name] = $row->Column_name;
					}

				}

				// Return all Column Indexes of array
				if (is_array($index)) {
					foreach ($index as $xy => $columns) {

						$structure .= ",\n";

						$c = 0;
						foreach ($columns as $column_key => $column_name) {

							$c++;

							$structure .= ($xy == "PRIMARY") ? "  PRIMARY KEY  (`{$column_name}`)" : false;
							$structure .= ($xy == "UNIQUE") ? "  UNIQUE KEY `{$column_key}` (`{$column_name}`)" : false;
							$structure .= ($xy == "INDEX") ? "  KEY `{$column_key}` (`{$column_name}`)" : false;
							$structure .= ($xy == "FULLTEXT") ? "  FULLTEXT `{$column_key}` (`{$column_name}`)" : false;

							$structure .= ($c < (count($index[$xy]))) ? ",\n" : false;

						}

					}

				}

				$structure .= "\n);\n\n";

				// Header
				$structure .= "-- \n";
				$structure .= "-- Dumping data for table `$table` \n";
				$structure .= "-- \n\n";

				// Dump data
				unset($data);
				$result     = mysql_query("SELECT * FROM `$table`");
				$num_rows   = mysql_num_rows($result);
				$num_fields = mysql_num_fields($result);

				for ($i = 0; $i < $num_rows; $i++) {

					$row = mysql_fetch_object($result);
					$data .= "INSERT INTO `$table` (";

					// Field names
					for ($x = 0; $x < $num_fields; $x++) {

						$field_name = mysql_field_name($result, $x);

						$data .= "`{$field_name}`";
						$data .= ($x < ($num_fields - 1)) ? ", " : false;

					}

					$data .= ") VALUES (";

					// Values
					for ($x = 0; $x < $num_fields; $x++) {
						$field_name = mysql_field_name($result, $x);

						$data .= "'" . str_replace('\"', '"', mysql_escape_string($row->$field_name)) . "'";
						$data .= ($x < ($num_fields - 1)) ? ", " : false;

					}

					$data.= ");\n";
				}

				$data.= "\n";

				$dump .= $structure . $data;
				$dump .= "-- --------------------------------------------------------\n\n";

			}

			return $dump;

		}

	}

}


  $dump = new MySQLDump();
  ob_start("ob_gzhandler");
  error_reporting(0);
  $s = $dump->dumpDatabase(MYSQL_DB);
  print $s;
}


function objects_list($table,$fields,$where,$order,$action_string) {
    if($where) $where .= " WHERE $where ";
    if($order) $order .= " ORDER BY $order ";
    $res = db_query("SELECT id, $fields FROM $table $where $order");
    return htmlquery_code($res,$action_string);
}


function link_button($link,$cap) {
  global $base_url;
  return "<input type=button value='$cap' onclick='window.location=\"{$base_url}$link\"'>";
}


function ses_set($var,$val) {
  return session_set($var,$val);
}

function ses_get($var,$def="") {
  return session_get($var,$def);
}

function db_object($table,$id,$props=false) {
  return db_object_get($table,$id,$props);
}
function form_link_table($link_table,$master_table,$master_field,$master_value,$items_table,$items_table_display_field) {

    global $tables;
    $title = table_long_alias($link_table)."s";
    page_header($title);

    $items_table_single = str_start($items_table,"s");
    if(str_prefix($items_table_single) == str_prefix($master_table)) {
      $items_table_single = str_no_prefix($items_table_single,str_prefix($master_table));
    }
    
    
    $master_object = db_object($master_table,$master_value);
    
    if(form_post("update")) {
        sql("START TRANSACTION");

        sql("DELETE FROM $link_table WHERE $master_field=%d",$master_value);
        
        if(isset($_REQUEST['cb'])) {
          $checkbox_values = $_REQUEST['cb'];
          foreach($checkbox_values as $checkbox_id=>$checkbox_value) {
            sql("INSERT INTO $link_table ($master_field,{$items_table_single}_id)
                      VALUES (%d,%d)",$master_value,$checkbox_id);
          }
        }
        sql("COMMIT");
        session_return("updated");
    }
    
    if(form_post("cancel")) {
        session_return("");
    }



    $rr = db_query("SELECT $items_table.id as id, $items_table_display_field, $master_field
                    FROM $items_table 
                    LEFT JOIN 
                    (SELECT * FROM $link_table WHERE $master_field=%d) as sub
                    ON ({$items_table_single}_id=$items_table.id OR $master_field is NULL)",$master_value);
    
    form_start();
    while($r=db_fetch_object($rr)) {
      form_checkbox($r->$items_table_display_field,"cb[$r->id]",strlen($r->$master_field)>0,"1");      
    }
    
    global $form;
    $form .= "<tr><td><td><input type=submit name=update value=OK>
                          <input type=submit name=cancel value=Cancel>";
    form_end();
    return form();
}


function menu_from_file($filename) {
  $lines = file($filename);
  $output = "";
  foreach($lines as $line) {
    $parts = explode(',',$line);
    //if($output) 
    $output .= "::";
    $output .= l($parts[0],$parts[1]);
  }
  return $output;
}


function page_pass_recover() {
   $o = "";

   if(form_post("submit")) {

	     if(strlen(trim(form_post("login")) )==0) {
			 $o .= errormsg("Can't process empty value.");
		 } else {

			 $exists = db_result(db_query("SELECT id FROM users WHERE username='%s'
					   OR email='%s'",form_post("login"),form_post("login")));

			if(!$exists) {
			   $o .= errormsg("User with such email isn't registered.");
			} else {
			  $user = db_object_get("users",$exists);
			  $message = "webpaper.co\r\n".
						 "Login(email): $user->email\r\n".
						 "Password: $user->password\r\n";

			  $headers = 'From: admin@webpaper.co' . "\r\n" .
						 'Reply-To: admin@webpaper.co' . "\r\n" .
						 'X-Mailer: PHP/' . phpversion();

			  if(mail($user->email,"webpaper.co login information",$message,$headers))
				$o .= "<br>Password has been sent<br>";
			  else
				$o .= errormsg("Can't send password.");
			}
		 }
	  
   }
   form_start();
   form_input("Email","login");
   form_submit("Send password","submit");

   $o .= form();

   return "$o";

}

function event($name) {
	if(function_exists($name)) {
         $name();
	}
}

function page_user_signup() {
    event("on_user_signup");

    form_start("","post"," name=signup_form ");
    form_input("Email","email",form_post("email"));
    form_password("Password","password","",""," class='password' ");
    form_password("Retype","retype");
    form_submit("Sign Up","Signup"," id=signup_submit ");
    form_end();

    $o = form();

    if (form_post("email")) {
      
      if(!form_post("password") || !form_post("retype")) {
        $o .= "Please fill all fields."; 
      } else
      if(form_post("password") != form_post("retype")) {
        $o .= "Retype doesn't match password.";
      } else {

            $exists = db_result(db_query("SELECT id FROM users WHERE email='%s'",form_post("email")));
            if ($exists) {
                $o .= errormsg("Such email (login) already in use.");
            } else {
                db_query("INSERT INTO users (email,password)
                         VALUES ('%s','%s')",form_post("email"),form_post("password"));


                $_SESSION['userid'] = db_last_id();
                redir(""); 
		die();


                $o .= "Successfully signed up";
            }
      }
    }   

    return "$o";
}

function uid() {
  if(isset($_SESSION['userid'])) 
	   return $_SESSION['userid'];
  else return "";
}


//////////////// ERROR HANDLING

function page_error() {
  setcookie("error_report",1);
  die("Error Reporting turned on");
}

function page_error_off() {
  setcookie("error_report",0);
  die("Error Reporting turned off");
}
function page_err_test() {
  trigger_error("Incorrect input vector, array of values expected", E_USER_WARNING);
}


function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

	$msg = "";

    switch ($errno) {
    case E_USER_ERROR:
        $msg .= "<b>ERROR</b> [$errno] $errstr<br />\n";
        $msg .= "  Fatal error on line $errline in file $errfile";
        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        $msg .= "Aborting...<br />\n";
//        exit(1);
        break;

    case E_USER_WARNING:
        $msg .= "<b>WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        $msg .= "<b>NOTICE</b> [$errno] $errstr<br />\n";
        break;

    default:
        $msg .= "Unknown error type: [$errno] $errstr<br />\n";
        break;
    }

    /* Don't execute PHP internal error handler */

    if(!isset($_COOKIE['error_report']) || $_COOKIE['error_report']==0) {
		
		if(filesize("site_errors.txt")>500*1000) {
			file_put_contents("site_errors.txt","");
		}

        $f = fopen("site_errors.txt", 'a');

		fwrite($f,"\r\n--- ".@date("n/j/Y G:i")." ------------------------\r\n"); 
		fwrite($f,$_SERVER['REQUEST_URI']."\r\n");
		fwrite($f,$msg."\r\n");

        ob_start();
		debug_print_backtrace();
		$s = ob_get_clean();
		fwrite($f,$s);
		
		fclose($f);
		echo 
       "<div style='background:#faa;font-family:Trebuchet MS;padding:10px;'>
         Sorry, but it seems we are having a hiccup.<br>
		 Please report this to site developer.<br>
		 Error mesage was saved to log.<br>
        {$_SERVER['REQUEST_URI']}
	   </div>";
	   die();

	} else {
		echo "<div style='background:#faa;'>";
		echo "$msg";
		echo "<pre>";
		debug_print_backtrace();
		echo "</pre>";
		echo "</div>";
	}
	return true;
}

set_error_handler("myErrorHandler");


function page_user_debug() {
  echo "<pre>";

  $user = db_object_get("users",uid());
  print_r($user);

  print_r($_SESSION);
  echo "</pre>";
  die();

}

function user_login($uid) {
  $_SESSION['userid'] = $uid;
}

function nice_urls(&$html) {
	if(NICE_URLS)
      $html = str_replace("?q=","",$html);
}

$script_uses = array();
function script_uses($scripts) {
  global $script_uses;
 
  $scripts = explode(',',$scripts);
  foreach($scripts as $script) {
    $script = trim($script);
    if(!file_exists($script)) {
      $script = "uses/".$script;
    }
    if(!file_exists($script))
      die("script file doesn't exists $script");
    if(in_array($script,$script_uses)) {
      continue;
    } else {
      $script_uses[] = $script;  
    }
  }
}

function script_uses_head() {
  $o = "";
  global $script_uses;
  foreach($script_uses as $script) {
    if(fileExt($script)=='js')
      $o .= "\r\n<script type='text/javascript' src='$script'></script>";
    if(fileExt($script)=='css')
      $o .= "\r\n<link rel='stylesheet' href='$script' type='text/css' />";
  }
  $script_uses = array();
  return $o."\r\n";
}

function die_no_template($o) {
    global $base_url;
    nice_urls($o);
    $o = "<base href='$base_url'/><meta http-equiv='Content-Type' content='text/html; charset=UTF-8;'>".script_uses_head().$o;    
    $o .="<!-- profiler report: \r\n".prf_report()."\r\n -->";
    translate_parse($o);

    die("$o");
}
function dh() {
  die("HERE");  
}

//TEMPLATES

function div_abs($left,$top,$w,$h,$bg) {
  if($bg) $bg = "background:$bg;";
  else $bg = "";
  return "<div style='position:absolute;margin-left:{$left}px;margin-top:{$top}px;width:{$w}px;height:{$h}px;$bg'>";

}

/* href, call and f */

function replace_my_tags(&$html) {
	if(!str_beg(self_q(),"admin/edit")) {
	  if(function_exists("on_my_tags")) on_my_tags($html);
	}

    preg_match_all("|{f[^}]*}|",$html,$matches);
	foreach($matches[0] as $value) {
	  $varname = substr($value,2,strlen($value)-3);
	  $parts = explode(":",$varname);
	  if(count($parts)<2) {
		  $fname = $parts[0];
		  $cut = 0;
	  } else {
		  $fname = $parts[0];
		  $cut = $parts[1];
	  }
	  $file = file_get_contents($fname);
	  if($cut) {
		  $file = substr($file,0,$cut)."...";
	  }
	  $html = str_replace("{f$varname}",$file,$html);
	}

    preg_match_all("|{call[^}]*}|",$html,$matches);
    foreach($matches[0] as $value) {
      $varname = substr($value,2+3,strlen($value)-3-3);
      $varname = $varname;
      $parts = explode(" ",trim($varname));
      if(function_exists($parts[0])) {
        $function = $parts[0];
        $content = $function(isset($parts[1])?$parts[1]:null,isset($parts[2])?$parts[2]:null,isset($parts[3])?$parts[3]:null,isset($parts[4])?$parts[4]:null,isset($parts[5])?$parts[5]:null,isset($parts[6])?$parts[6]:null);
        $html = str_replace("{call$varname}",$content,$html);
      }
    }
    
    preg_match_all("|{href[^}]*}|",$html,$matches);
    foreach($matches[0] as $value) {
      $varname = substr($value,2+3,strlen($value)-3-3);
      $varname = $varname;
      $parts = explode(" ",trim($varname));
      global $base_url;
	  $background = "";
	  if(isset($parts[5])) {
		  $background = 'background:'.$parts[5].";";
	  }
	  $loc = $parts[4];
	  if(!str_beg($loc,"http") && !str_beg($loc,"javascript")) $loc = $base_url.$loc;
      $content = "<div style='cursor:pointer;position:absolute;margin-left:{$parts[0]}px;margin-top:{$parts[1]}px;width:{$parts[2]}px;height:{$parts[3]}px;{$background}z-index:100;' onclick='window.location=\"$loc\"'><a href={$parts[4]}></a></div>";
      $html = str_replace("{href$varname}",$content,$html);
    }
}

function replace_files(&$html) {
    preg_match_all("|{![^}]*}|",$html,$matches);
    foreach($matches[0] as $value) {
      $varname = substr($value,2,strlen($value)-3);
      
      if(str_end($varname,".html") && file_exists("uses/".$varname)) {
        $content = file_get_contents("uses/".$varname);
        replace_files($content);
        $html = str_replace("{!$varname}",$content,$html);
      }
      
      if(str_end($varname,".js") && file_exists("uses/".$varname)) {
        $content = "<script src='uses/$varname'></script>";
        $html = str_replace("{!$varname}",$content,$html);
      }
      
      if(str_end($varname,".png") && file_exists("images/".$varname)) {
        $content = "<img src='images/$varname'>";
        $html = str_replace("{!$varname}",$content,$html);
      }
      
      if(str_end($varname,".css") && file_exists("uses/".$varname)) {
       	$content = "<link rel='stylesheet' href='uses/$varname' type='text/css' />";
        $html = str_replace("{!$varname}",$content,$html);
      }
      
    }
}

function template($name="",$varname1="",$varval1="",$varname2="",$varval2="",$varname3="",$varval3="") {
  if(!$name) $name = $GLOBALS['def_template'];
  $fname = "uses/".$name.".tmpl.html";
  if(!file_exists($fname)) {
    $fname = "uses/".$name.".html";
  }
  
  if(file_exists($fname)) {
    $html = file_get_contents($fname);
    template_set($html,$varname1,$varval1,$varname2,$varval2,$varname3,$varval3);
    
    replace_files($html);
    replace_my_tags($html);
    replace_globals($html);
    
    return $html;
  }
  else
    die("template: $name not found");
}

function template_set(&$html,$varname,$varvalue,$varname2="",$varvalue2="",$varname3="",$varvalue3="",$varname4="",$varvalue4="") {
  if(!$varname) return;
  $html = str_replace('{!'.$varname.'}',$varvalue,$html);
  $html = str_replace('$'.$varname,$varvalue,$html);
  if($varname2) template_set($html,$varname2,$varvalue2);
  if($varname3) template_set($html,$varname3,$varvalue3);
  if($varname4) template_set($html,$varname4,$varvalue4);
  
}

function doc_elements_add($html) {
  
      global $doc_elements;
      $doc_elements .= $html;

}
function str_beg($s,$beg) {
    return substr($s,0,strlen($beg))==$beg;
}

function user($force_refresh=false) {
  if($force_refresh || !isset($GLOBALS["user-cache"])) {
	  $GLOBALS["user-cache"] = db_object_get("users",uid());
  }
  if(function_exists("on_user"))
     on_user($GLOBALS["user-cache"]);
  return $GLOBALS["user-cache"];
}

function table_field($table,$field) {
  db_query("ALTER TABLE $table ADD $field");  
}

function db_objects_get($table,$where_and_limit) {
  return db_fetch_objects(db_query("SELECT * FROM $table WHERE $where_and_limit"));
}

function die_def_template($o) {
  global $def_template;
  if(file_exists("uses/$def_template.html")) {
    $html = template($def_template,"content",$o);
  } else {
    $html = $o;
  }
  
  nice_urls($html);
  global $base_url;
  $html = "<base href='$base_url'/>".script_uses_head().$html;
  die($html);  
}
function session_set($name,$value) {
  global $apptitle;
  $_SESSION[$apptitle.$name] = $value;
}
function session_get($name,$def="") {
  global $apptitle;
  if(!isset($_SESSION[$apptitle.$name])) return $def;
  return $_SESSION[$apptitle.$name];
  
}

function mail_from($to,$subject,$message,$from) {
  $headers = "From: $from" . "\r\n" .
	     "Reply-To: $from" . "\r\n" .
	     'X-Mailer: PHP/' . phpversion();

  if(!mail($to,$subject,$message,$headers)) {
    echo "error while sending email";
  }
}
function str_limit($s,$len,$ending=-1) {

    if (mb_strlen($s,"UTF-8")<$len) return $s;

    if ($ending==-1) $ending = $len/2;

    return mb_substr($s,0,$len-4-$ending,"UTF-8")." ...".mb_substr($s,mb_strlen($s,"UTF-8")-$ending,$ending,"UTF-8");

}

function replace_globals(&$template) {
  preg_match_all("|{![^}]*}|",$template,$matches);
  foreach($matches[0] as $value) {
    $varname = substr($value,2,strlen($value)-3);
    if(strpos($varname,"->")) {
      $parts = split("->",$varname);
      if(isset($GLOBALS[$parts[0]]->$parts[1])) {
         $template = str_replace("{!$varname}",$GLOBALS[$parts[0]]->$parts[1],$template);
      } else {
        $template = str_replace("{!$varname}","",$template);
      }
    } else    
    if(!isset($GLOBALS[$varname])) 
      $template = str_replace("{!$varname}","",$template);
    else 
      $template = str_replace("{!$varname}",$GLOBALS[$varname],$template);
  }
}
function use_template($template_name) {
  if(file_exists("uses/{$template_name}.html"))
  $GLOBALS['template'] = file_get_contents("uses/{$template_name}.html");
}

function translate_parse(&$template) {
  preg_match_all("|{~[^}]*}|",$template,$matches);
  foreach($matches[0] as $value) {
    $varname = substr($value,2,strlen($value)-3);
    $translate = _T($varname);
    if($translate) {
      $template = str_replace("{~$varname}",$translate,$template);
    }
  }
}

function weight_fix($table) {
  db_query("UPDATE $table SET weight=id WHERE weight is null OR weight=0");
}

function page_all_vars() {
  requires_admin();
  global $modules;
  
  $mods = $modules;
  $mods[] = "app";
  $_REQUEST['q'] = '/vars';
  
  foreach($mods as $mod) {
    $func_name = "page_".$mod."_vars";
    if(function_exists($func_name)) {
      ln("----");
      ln("<font color=red>$func_name</font>");
      $func_name();
    }
    
    
  }
}

function self_q() {
  return $GLOBALS['self_q'];  
}


function setting($field) {
  return db_result(db_query("SELECT $field FROM settings"));
}

function table_drop($tablename,$fieldname) {
  db_query("ALTER TABLE $tablename DROP $fieldname");
}

function arg($i) {
  global $args;
  if(!isset($args[$i])) return null;
  else return $args[$i];
}

function page_error_test() {
  $i = 100 / 0;

}

function direct_post($table) {
  global $tables;
  $text = "";
  if($tables[$table]['directpost']) {
     foreach($_REQUEST as $key=>$val) {
		if($key==$table."_submit") continue;
        if(str_beg($key,$table."_")) {
           $cap = $key;
		   if(isset($_REQUEST['title_'.$key])) {
			   $cap = $_REQUEST['title_'.$key];
		   }
		   $text .= "$cap:".$val." ";
		}
	 }
	 db_query("INSERT INTO $table (text) VALUES ('%s')",$text);
  } else {
	  die ("tables['directpost'] is not set");
  }
}

function table_form_post($table, $answer) {
   if(form_post($table."_submit")) {
	  direct_post($table);
	  if(isset($_REQUEST["message_".$table])) {
		  return $_REQUEST["message_".$table];
	  } else {
		  return "Record posted";
	  }
   }
   return "";
}

function table_create($tablename,$fielddefs="") {
  if($fielddefs) $fielddefs .= ", ";
  db_query("CREATE TABLE $tablename (
  id int(11) NOT NULL auto_increment, $fielddefs
  PRIMARY KEY  (id))");
}

function table_alter($tablename,$fielddef) {
  db_query("ALTER TABLE $tablename ADD $fielddef");
}

?>
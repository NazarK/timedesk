<?php

class LogNode {
  var $total_time = 0;
  var $start_time = 0;
  var $id;
  var $parent;
  var $subs = array();
  var $count = 0;
  function start() {
	  $this->start_time = microtime_float();
  }

  function stop() {
	  $this->total_time += microtime_float()-$this->start_time;
	  $this->count++;
  }

  function report($level=0) {
	$o = "";
	if($this->id != "root") {
	  $o .= str_repeat(' ',$level)."$this->id: ".(round($this->total_time*100)/100)." s";
	} 

	if($this->parent) {
		if($this->parent->total_time)
		$o .= "(".round(100*$this->total_time/$this->parent->total_time)."%) ".$this->count;
	}
	$o .= "\r\n";
    foreach($this->subs as $sub) {
       $o .= $sub->report($level+1);
	}
	return $o;
  }

}

$prf_root = new LogNode();
$prf_root->id = "root";
$prf_root->parent = 0;
$prf_active = &$prf_root;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function prf_start($id) {
  global $prf_active;
  if(!isset($prf_active->subs[$id])) {
     $prf_active->subs[$id] = new LogNode();
	 $prf_active->subs[$id]->id = $id;
	 $prf_active->subs[$id]->parent = $prf_active;
  }
  $prf_active = $prf_active->subs[$id];
  $prf_active->start();
}

function prf_end() {
  global $prf_active;
  $prf_active->stop();
  if($prf_active->id != "root") {
     $prf_active = $prf_active->parent;
  } else
	 $prf_active = &$prf_root;
}

function prf_report() {
  global $prf_root;
  return $prf_root->report();  
}


function page_profiler_test() {
  prf_start("main");
  prf_start("sub");
  prf_start("sub3");
  sleep(1);
  prf_end();
  prf_end();
  prf_end();
  global $prf_root;
  echo "<pre>";
  print_r($prf_root);
  echo "</pre>";

  echo "<pre>".prf_report()."</pre>";
  die();

}

?>
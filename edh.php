<?php


include_once(__DIR__."\global.php");


$time = time();
$date = date('Y-m-d', $time);

$time = -microtime(true);
$GLOBALS["cards"] = 0;
$GLOBALS["requests"] = 0;
$GLOBALS["errors"] = array();
$fetch = "";
echo "\n\n\nScript Execution Started, date ".$date."\n\n";

fetchAll($date);

$time += microtime(true);
echo "\n\n\n-".$fetch."-   Script Execution Completed; TIME:".round($time/60, 2)." minutes, fetch: ".$GLOBALS["cards"]." entries over ".$GLOBALS["requests"]." requests";


function fetchAll($date){
	
	$context = getContext();
	
	$db = DB::app();
	$toDo = $db->getSetsToPull($date);

	foreach ($toDo as $set){
		//if ($set["setcode"] != "MHZ"){continue;}
		echo("**** ".$set["setname"]." / ".$set["setcode"].", id ".$set["id"].LR);
		//return;
		//continue;
		switch ($set["type"]){
			case 0: $pulled = crawlBaseSet($db, $context, $set); break;
			case 1: $pulled = crawlGameBoxes($db, $context, $set); break;
			case 2: $pulled = crawlFreeURL($db, $context, $set); break;
		}
		//continue;
		//msg("pulls: ".sizeof($pulled)); return;
		if (!(writeAndClose($db, $set["setcode"], $date, $pulled))){
			msg("error on set ".$set["setcode"]); break;
		}
	}
	
	logErrors();

}

?>
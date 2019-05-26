<?php


header('Content-Type: text/html; charset=utf-8');
include_once(__DIR__."\global.php");
include_once(__DIR__."\libs\dump.php");

define("CONTEXT", stream_context_create(
	    array(
	        "http" =>
				array(
				  //  "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				    "header" => "Accept-Charset: UTF-8"
				)
			)
	)
);



$time = time();
$date = date('d.m.Y', $time);

$time = -microtime(true);

echo "\n\n\nScript Execution Started \n\n";

fetchAll($date);

$time += microtime(true);

echo "\n\n\n-".$fetch."-   Script Execution Completed; TIME:".round($time/60, 2)." minutes, fetch: ".$GLOBALS["cards"]." entries over ".$GLOBALS["requests"]." requests";



function fetchAll($day){
	crawl($day);
	logErrors();
}

function crawl($date){

	echo "\n\n*** Beginning \n";

	$url = "https://www.mtggoldfish.com/tournaments/modern#paper";

	$html = file_get_html($url, false, CONTEXT);

	$headers = $html->find(".decks-sidebar h4 > a");
	//$tables = $html->find(".decks-sidebar table");

	//message("deck tables: ".sizeof($tables));

	for ($i = 0; $i < sizeof($headers); $i+=2){
		processTour($headers[$i]);
		die();
	}

	message("tables: ".sizeof($ele));
	die();


	$html->clear(); 
	unset($html);
}


function processTour($header){
	message("processTour");

	$prop = "href";
	$tourURL = "https://www.mtggoldfish.com/".$header->{$prop};

	message($tourURL);

	$html = file_get_html($tourURL, false, CONTEXT);
	$rows = $html->find(".table-condensed", 0)->find("tr");

	for ($i = 1; $i < sizeof($rows); $i+=2){
		$string = $rows[$i]->find("td", 2)->find("a", 0)->{$prop};
		message($string);
		$deckid = substr($string, 6, strlen($string));
		$downloadURL = "https://www.mtggoldfish.com/deck/download/".$deckid;

		handleDecklist($downloadURL, $deckid);
	}

	$html->clear(); 
	unset($html);
}



function handleDeckList($url, $deckid){
	file_put_contents(dirname(__FILE__).'/lists/'.$deckid.'.txt',  fopen($url, "r"));
	return;
	die();
	$handle = fopen($url, "r");

	if (!$handle){die();}
	if ($handle) {
	    while (($buffer = fgets($handle, 4096)) !== false) {
	        print_r($buffer);
	    }
	    if (!feof($handle)){
	    	message("EoF");
	    }
	    fclose($handle);
    }
}



?>
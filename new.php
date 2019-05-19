<?php


include_once(__DIR__."\global.php");

define("CONTEXT", stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
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

	$headers = $html->find(".decks-sidebar h4");
	$tables = $html->find(".decks-sidebar table");

	message("deck tables: ".sizeof($tables));

	for ($i = 0; $i < sizeof($tables); $i++){
		processTable($headers[$i], $tables[$i]);
		die();
	}

	message("tables: ".sizeof($ele));
	die();


	$html->clear(); 
	unset($html);
}


function processTable($header, $table){
	message("processing table, children: ".sizeof($table->children()));
	$rows = $table->find("tbody > tr");
	message("rows ".sizeof($rows));

	$topURL = "https://www.mtggoldfish.com/";

	$prop = "href";
	$headEntry = $header->find("a", 1)->{$prop};
	$tourURL = $topURL."tournaments/".$headEntry;

	message($tourURL);


	for ($i = 1; $i < sizeof($rows); $i++){
		$td = $rows[$i]->find(".col-deck > a", 1);
		$deckURL = $topURL.($td->{$prop});
		message($deckURL);

		processDeck($deckURL);
	}

	die();
	$url = "https://www.mtggoldfish.com/";
	message($table->find(".col-deck")->innertext);
}



function processDeck($url){

	$html = file_get_html($url, false, CONTEXT);
	$rows = $html->find(".deck-view-deck-table", 2)->find("tbody > tr");

	message("rows: ".sizeof($rows));
	die();
	$html->clear(); 
	unset($html);
}

?>
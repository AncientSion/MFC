<?php

include_once(__DIR__."\global.php");




$time = time();
$date = date('d.m.Y', $time);
echo "\n\n\nScript Execution Started \n\n";



$context = stream_context_create(
    array(
        "http" =>
			array(
				"header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
			)
		)
);


$baseURL = "https://edhrec.com/top/";
$colors = array("w", "u", "b", "r", "g", "colorless", "multi");
//$colors = array("w", "u");

for ($i = 0; $i < sizeof($colors); $i++){
	echo "Fetching: ".$colors[$i]."\n";
	$url = $baseURL.$colors[$i];

	$html = file_get_html($url, false, $context);

	$data = $html->find("script", 12)->innertext;
	$data = substr($data, 18, -1);

	$json = json_decode($data, false);
	$cards = $json->cardlists[0];


	$write = array("date" => $date, "code" => "EDH", "color" => $colors[$i], "data" => array());

	for ($j = 0; $j < sizeof($cards->cardviews); $j++){
		//var_export($cards->cardviews[0]->label);

		//$avail = str_replace(",", ".", substr($foilPrice, $start, strpos($foilPrice, " ")))
		$label = $cards->cardviews[$j]->label;
		$pos =  strpos($label, ">");
		$baseAvail = substr($label, 3, strpos($label, " ", 4)-3);
		$foilAvail = substr($label, $pos+1, strpos($label, "%") - $pos-1);

		

		$write["data"][] = array(
			"name" => $cards->cardviews[$j]->name,
			"baseAvail" => floor($baseAvail),
			"foilAvail" => floor($foilAvail)
		);
	}


	$file = fopen(__DIR__."/output/" ."EDH.json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($write)."\n"."]}");
	fclose($file);
}


$time += microtime(true);
echo "Script Execution Completed; TIME:".round($time)." seconds !";


?>
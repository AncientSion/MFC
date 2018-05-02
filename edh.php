<?php

include_once(__DIR__."\global.php");

//getEDH();
getSimple();


function getEDH(){
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
	echo "Script Execution Completed; TIME:".round($time/60, 2)." seconds !";
}


function getSimple(){
	$time = time();
	$date = date('d.m.Y', $time);
	$time = -microtime(true);
	$GLOBALS["gets"] = 0;
	$fetch = "";
	echo "\n\n\nScript Execution Started \n\n";

	/*
	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T",
				"method" => "GET",
					"content" => http_build_query(
						array(
							"sortBy" => "englishName",
							"sortDir" => "asc",
							"view" => "list"
						)
					)
				)			
			)
	);
	*/
	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);

	//$sets = array("Khans of Tarkir", "Dragons of Tarkir", "Fate Reforged");
	//$codes = array("KTK", "DTK", "FRF");
	$sets = array("Ravnica: City of Guilds", "Planeshift", "Planar Chaos", "Rise of the Eldrazi");
	$codes = array("RAV", "PLS", "PLC", "ROE");

	$get = "?sortBy=englishName&sortDir=asc&view=list";


	//for ($i = 0; $i < 1; $i++){
	for ($i = 0; $i < sizeof($codes); $i++){
		echo "\n\n*** Beginning - ".$sets[$i]." / ".$codes[$i]." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $sets[$i], "data" => array());

		$exit = 0;
		$page = 0;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . urlencode($sets[$i])."?sortBy=englishName&sortDir=asc&view=list";
			if ($page){$url .= "&resultsPage=".$page;}

			echo "URL: ".$url."\n";
			$html = file_get_html($url, false, $context);
			$rows = $html->find(".MKMTable", 0)->find("tr");

			if (!$rows){echo "----- Invalid Page, ending Set on page ".$page."\n"; $exit = 1; break;}

			for ($k = 1; $k < sizeof($rows)-1; $k++){
			//for ($k = 1; $k < 3; $k++){
				$basePrice = $rows[$k]->children(5)->plaintext;
				$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));
				$foilPrice = $rows[$k]->children(7)->plaintext;
				$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));

				$set["data"][] = array(
					"name" => $rows[$k]->children(2)->plaintext, 
					"rarity" => "Special", 
					"baseAvail" => intval($rows[$k]->children(4)->plaintext),
					"basePrice" => floatval($basePrice),
					"foilAvail" => intval($rows[$k]->children(6)->plaintext),
					"foilPrice" => floatval($foilPrice)
				);
			}

			$page++;

			//if ($page > 2){$exit = 1; break;}
		}

		writeAndClose($codes[$i], $set);
	}
}

?>
<?php

return;

include_once(__DIR__."\global.php");


$time = time();
$date = date('d.m.Y', $time);

$time = -microtime(true);
$GLOBALS["cards"] = 0;
$GLOBALS["requests"] = 0;
$GLOBALS["errors"] = array();
$fetch = "";
echo "\n\n\nScript Execution Started \n\n";
fetchAll($date);
$time += microtime(true);
echo "\n\n\n-".$fetch."-   Script Execution Completed; TIME:".round($time/60, 2)." minutes, fetch: ".$GLOBALS["cards"]." cards over ".$GLOBALS["requests"]." requests";


function fetchAll($day){
	
	getEDH($day);
	getBoxPrices($day);

	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);
	
			
	$data = json_decode(file_get_contents(__DIR__."/input/avail.json"), TRUE);
	$codes = $data["codes"];
	$names = $data["names"];	

	nonFoil($day, $codes[0], $names[0], $context);
	foil($day, $codes[1], $names[1], $context);
	mixed($day, $codes[2], $names[2], $context);
	
	echo "\n\n";

	if (!sizeof($GLOBALS["errors"])){
		echo "NO ERRORS !";
	}
	else {
		for ($i = 0; $i < sizeof($GLOBALS["errors"]); $i++){
			echo "error: ".$GLOBALS["errors"][$i]."\n";
		}
	}

}

function getEDH($date){

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

	for ($i = 0; $i < sizeof($colors); $i++){
		echo "Fetching: ".$colors[$i]."\n";
		$url = $baseURL.$colors[$i];

		$html = file_get_html($url, false, $context);
		
		$scripts = $html->find("script");
		for ($j = 0; $j < sizeof($scripts); $j++){
			if (substr($scripts[$j], 8, 5) == "const"){
				$data = $scripts[$j]->innertext;
			}
			//echo substr($data[$j], 0, 10);
			//echo "\n";
		}
		
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
}

function nonFoil($date, $codes, $names, $context){

	for ($i = 0; $i < sizeof($codes); $i++){
		echo "\n\n*** Beginning - ".$names[$i]." / ".$codes[$i]."\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $names[$i], "data" => array());

		$exit = 0;
		$page = 0;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . urlencode($names[$i])."?sortBy=englishName&sortDir=asc&view=list";
			if ($page){$url .= "&resultsPage=".$page;}

			echo "URL: ".$url."\n";
			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".MKMTable", 0)->find("tr");

			if (!$rows){echo "----- Invalid Page, ending Set on page ".$page."\n"; $exit = 1; break;}

			for ($k = 1; $k < sizeof($rows)-1; $k++){
				$name = $rows[$k]->children(2)->plaintext;

				$baseAvail = $rows[$k]->children(4)->plaintext;
				$basePrice = $rows[$k]->children(5)->plaintext;
				$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));

				$foilAvail = 0;
				$foilPrice = 0.00;

				doAdd($name, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
			}

			$page++;

			if ($page > 15){$exit = 1; echo "\n LOOP ERROR ---- SET: ".$codes[$i]."/".$names[$i]; $GLOBALS["errors"][] = $codes[$i]; break;}
		}

		writeAndClose($codes[$i], $set);
	}
}

function foil($date, $codes, $names, $context){

	for ($i = 0; $i < sizeof($codes); $i++){
		echo "\n\n*** Beginning - ".$names[$i]." / ".$codes[$i]." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $names[$i], "data" => array());

		$exit = 0;
		$page = 0;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . urlencode($names[$i])."?sortBy=englishName&sortDir=asc&view=list";
			if ($page){$url .= "&resultsPage=".$page;}

			echo "URL: ".$url."\n";
			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".MKMTable", 0)->find("tr");

			if (!$rows){echo "----- Invalid Page, ending Set on page ".$page."\n"; $exit = 1; break;}

			for ($k = 1; $k < sizeof($rows)-1; $k++){
				$name = $rows[$k]->children(2)->plaintext;

				$baseAvail = 0;
				$basePrice = 0.00;

				$foilAvail = $rows[$k]->children(4)->plaintext;
				$foilPrice = $rows[$k]->children(5)->plaintext;
				$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));

				doAdd($name, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
			}

			$page++;

			if ($page > 15){$exit = 1; echo "\n LOOP ERROR ---- SET: ".$codes[$i]."/".$names[$i]; break;}
		}

		writeAndClose($codes[$i], $set);
	}
}

function mixed($date, $codes, $names, $context){
	
	for ($i = 0; $i < sizeof($codes); $i++){
		echo "\n\n*** Beginning - ".$names[$i]." / ".$codes[$i]." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $names[$i], "data" => array());

		$exit = 0;
		$page = 0;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . urlencode($names[$i])."?sortBy=englishName&sortDir=asc&view=list";
			if ($page){$url .= "&resultsPage=".$page;}

			echo "URL: ".$url."\n";
			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".MKMTable", 0)->find("tr");

			if (!$rows){echo "----- Invalid Page, ending Set on page ".$page."\n"; $exit = 1; break;}
			else if (sizeof($rows[0]->children()) < 8){echo "----- BREAKUP Page, ending Set on page ".$page."\n"; $exit = 1; break;}

			for ($k = 1; $k < sizeof($rows)-1; $k++){
				$name = $rows[$k]->children(2)->plaintext;
				//echo $name."\n";

				$baseAvail = $rows[$k]->children(4)->plaintext;
				$basePrice = $rows[$k]->children(5)->plaintext;
				$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));

				$foilAvail = $rows[$k]->children(6)->plaintext;
				$foilPrice = $rows[$k]->children(7)->plaintext;
				$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));

				doAdd($name, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
			}

			$page++;

			if ($page > 15){$exit = 1; echo "\n LOOP ERROR ---- SET: ".$codes[$i]."/".$names[$i]; break;}
		}

		writeAndClose($codes[$i], $set);
	}
}

function getBoxPrices($date){
	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);

	$set = array("date" => $date, "code" => "BOXES", "set" => "Booster Boxes", "data" => array());



	for ($i = 0; $i < 5; $i++){
		$url = "https://www.cardmarket.com/en/Magic/Products/Booster+Boxes?name=&idExpansion=0&onlyAvailable=&sortBy=englishName&sortDir=asc&view=list";

		if ($i){$url .= "&resultsPage=".$i;}

		echo "paging: ".$i."\n";
		$html = file_get_html($url, false, $context);
		$rows = $html->find(".MKMTable", 0)->children(1)->children();

		for ($j = 0; $j < sizeof($rows); $j++){
			$name = $rows[$j]->children(2)->children(0)->innertext;
			$baseAvail = $rows[$j]->children(3)->children(0)->innertext;
			$basePrice = 0.00;
			if ($baseAvail){
				$basePrice = $rows[$j]->children(4)->children(0)->innertext;
				$basePrice = str_replace(",", ".", $basePrice);
				$basePrice = substr($basePrice, 0, strlen($basePrice)-9);
			}

			$set["data"][] = array("name" => $name, "baseAvail" => intval($baseAvail), "basePrice" => floatval($basePrice), "foilAvail" => intval(0), "foilPrice" => floatval(0));
		}
	}

	writeAndClose("BOXES", $set);
}

function doAdd($name, $baseAvail, $basePrice, $foilAvail, $foilPrice, &$set){
	$set["data"][] = array(
		"name" => $name, 
		"rarity" => "Special", 
		"baseAvail" => intval($baseAvail),
		"basePrice" => floatval($basePrice),
		"foilAvail" => intval($foilAvail),
		"foilPrice" => floatval($foilPrice)
	);
}




function doConvert(){
	$data = json_decode(
	  '{
		"codes": [
			["A25", "EMA"],
			["LEB", "LEG", "TMP", "STH"],
			["MIR", "VIS"],
			["PLS", "LRW", "MOR", "SHM", "EVE", "RAV", "PLC", "GTC", "WWK", "ROE", "MRD", "5DN", "DST", "SOM", "MBS", "NPH", "KTK", "DTK", "FRF", "SOI", "EMN", "KLD", "AER", "AKH", "HOU", "XLN", "RIX", "DOM"],
			[],
			["BOXES"]
		],
		"names":
		[
			["Masters 25", "Eternal Masters"],
			["Beta", "Legends", "Tempest", "Stronghold"],
			["Mirage", "Visions"],
			["Planeshift", "Lorwyn", "Morningtide", "Shadowmoor", "Eventide", "Ravnica: City of Guilds", "Planar Chaos", "Gatecrash", "Worldwake", "Rise of the Eldrazi", "Mirrodin", "Fifth Dawn", "Darksteel", "Scars of Mirrodin", "Mirrodin Besieged", "New Phyrexia", "Khans of Tarkir", "Dragons of Tarkir", "Fate Reforged", "Shadows over Innistrad", "Eldritch Moon", "Kaladesh", "Aether Revolt", "Amonkhet", "Hour of Devastation", "Ixalan", "Rivals of Ixalan", "Dominaria"],
			["Zendikar Expeditions", "Kaladesh Inventions", "Amonkhet Invocations"],
			["Booster Boxes"]
		]
	}'
	, FALSE);

	//var_export($data); return;

	$codes = 'array(';

	for ($i = 0; $i < sizeof($data->codes); $i++){
	  for ($j = 0; $j < sizeof($data->codes[$i]); $j++){
	    $codes .= '"'.$data->codes[$i][$j].'", ';
	  }
	}

	$codes = substr($codes, 0, strlen($codes)-2);
	$codes .= ');';


	$names = 'array(';

	for ($i = 0; $i < sizeof($data->names); $i++){
	  for ($j = 0; $j < sizeof($data->names[$i]); $j++){
	    $names .= '"'.$data->names[$i][$j].'", ';
	  }
	}

	$names = substr($names, 0, strlen($names)-2);
	$names .= ');';


	array("A25", "EMA", "PLS", "LRW", "MOR", "SHM", "EVE", "RAV", "PLC", "GTC", "WWK", "ROE", "MRD", "5DN", "DST", "SOM", "MBS", "NPH", "KTK", "DTK", "FRF", "SOI", "EMN", "KLD", "AER", "AKH", "HOU", "XLN", "RIX", "DOM", "EXP", "MPS", "MPS_AKH", "BOXES");

	array("Masters 25", "Eternal Masters", "Planeshift", "Lorwyn", "Morningtide", "Shadowmoor", "Eventide", "Ravnica: City of Guilds", "Planar Chaos", "Gatecrash", "Worldwake", "Rise of the Eldrazi", "Mirrodin", "Fifth Dawn", "Darksteel", "Scars of Mirrodin", "Mirrodin Besieged", "New Phyrexia", "Khans of Tarkir", "Dragons of Tarkir", "Fate Reforged", "Shadows over Innistrad", "Eldritch Moon", "Kaladesh", "Aether Revolt", "Amonkhet", "Hour of Devastation", "Ixalan", "Rivals of Ixalan", "Dominaria");
}

?>
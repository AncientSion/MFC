<?php


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
echo "\n\n\n-".$fetch."-   Script Execution Completed; TIME:".round($time/60, 2)." minutes, fetch: ".$GLOBALS["cards"]." entries over ".$GLOBALS["requests"]." requests";


function fetchAll($day){

	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);
	
	//getEDH($day);
	
			
	$data = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);
	$codes = $data["codes"];
	$names = $data["names"];
	
	crawl($day, $codes[0], $names[0], 1, 0, $context); // non foils
	//crawl($day, $codes[1], $names[1], 1, 1, $context); // reg sets
	crawl($day, $codes[2], $names[2], 1, 0, $context); // promos
	getSets($day, $context); // FTV sealed
	getBoxPrices($day, $codes[4], $names[4], $context); // boxes
	
	logErrors();

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

function crawl($date, $codes, $names, $nonFoil, $foil, $context){
	
	//$codes = array("RNA");
	//$names = array("Ravnica Allegiance");
	
	for ($i = 0; $i < sizeof($codes); $i++){
		echo "\n\n*** Beginning - ".$names[$i]." / ".$codes[$i]."\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $names[$i], "data" => array());

		$exit = 0;
		$page = 1;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . doReplace($names[$i])."?onlyAvailable=on&sortBy=locName_asc&perSite=50";
			$url .= "&site=".$page;
						
			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".table-body", 0)->children();

			for ($k = 0; $k < sizeof($rows)-1; $k++){
				$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->plaintext;
				$baseAvail = 0;
				$basePrice = 0.00;
				$foilAvail = 0;
				$foilPrice = 0.00;
				
				if ($nonFoil){
					$baseAvail = $rows[$k]->children(4)->children(0)->plaintext;
					$basePrice = $rows[$k]->children(5)->plaintext;
					$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));
				}
				if ($foil){
					$foilAvail = $rows[$k]->children(6)->plaintext;
					$foilPrice = $rows[$k]->children(7)->plaintext;
					$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));
				}
				
				
				//echo $name."/".$baseAvail."/".$basePrice."/".$foilAvail."/".$foilPrice."\n";				
				doAdd($name, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
			}
			
			$page++;
			
			if (sizeof($rows) < 50){
				//echo "last site of set \n\n";
				break;
			}
			else if ($page >= 15){
				echo "ERROR \n\n";
				$GLOBALS["errors"][] = $codes[$i];
				break;
			}
		}
		writeAndClose($codes[$i], $set);
		//return;
	}
}

function getBoxPrices($date, $codes, $names, $context){

	//$names = array("Pokemon", "Magic", "YuGiOh", "Vanguard", "DragonBallSuper", "FoW", "MyLittlePony", "Spoils", "StarWarsDestiny", "WoW", "WeissSchwarz", "DragoBorne", "FinalFantasy");
	//$codes = array("_PCG", "_MTG", "_YGO", "_CFV", "_DGB", "_FOW", "_MLP", "_SPOILS", "_SWD", "_WOW", "_WS", "_DBS", "_FF");
	
	for ($i = 0; $i < sizeof($names); $i++){

		$game = substr($names[$i], 0, strlen($names[$i])-6);
	
		$set = array("date" => $date, "code" => $codes[$i], "set" => $codes[$i]." Boxes", "data" => array());	
		
		for ($j = 1; $j < 10; $j++){			
			$url = "https://www.cardmarket.com/en/".$game."/Products/Booster-Boxes?mode=&searchString=&onlyAvailable=on&sortBy=locName_asc&perSite=50";
			$url .= "&site=".$j;

			//echo "paging: ".$names[$i]." / ".$j."\n";
			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".table-body", 0)->children();

			for ($k = 0; $k < sizeof($rows); $k++){
				$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->innertext;
				$baseAvail = $rows[$k]->children(4)->children(0)->innertext;
				$basePrice = 0.00;
				if ($baseAvail){
					$basePrice = $rows[$k]->children(5)->innertext;
					$basePrice = str_replace(",", ".", $basePrice);
					$basePrice = substr($basePrice, 0, strlen($basePrice)-9);
				}

				$set["data"][] = array("name" => $name, "baseAvail" => intval($baseAvail), "basePrice" => floatval($basePrice), "foilAvail" => intval(0), "foilPrice" => floatval(0));
			}
			
			if (sizeof($rows) < 50){
				echo "last page - ";
				break;
			}
		}


	/*	foreach ($set["data"] as $entry){
			echo $entry["name"]."\n";
		}
		echo sizeof($set["data"]);			
		return;
	*/
		writeAndClose($codes[$i], $set);
	}
}

function getSets($date, $context){
	
	$urls = array();
	$urls[] = "https://www.cardmarket.com/en/Magic/Products/Sets?searchString=Sealed&sortBy=sellVolume_desc&perSite=50";
	
	$codes = array();
	$codes[] = "SETS";
	
	for ($i = 0; $i < sizeof($urls); $i++){
	
		$set = array("date" => $date, "files" => $codes[$i], "set" => $codes[$i], "data" => array());	
		$baseUrl = $urls[$i];
		
		for ($j = 0; $j < 10; $j++){			
			$url = $baseUrl."&site=".$j;

			$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;
			$rows = $html->find(".table-body", 0)->children();

			for ($k = 0; $k < sizeof($rows); $k++){
				$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->innertext;
				$baseAvail = $rows[$k]->children(4)->children(0)->innertext;
				$basePrice = 0.00;
				if ($baseAvail){
					$basePrice = $rows[$k]->children(5)->innertext;
					$basePrice = str_replace(",", ".", $basePrice);
					$basePrice = substr($basePrice, 0, strlen($basePrice)-9);
				}

				//echo $name."/".$baseAvail."/".$basePrice."\n";
				$set["data"][] = array("name" => $name, "baseAvail" => intval($baseAvail), "basePrice" => floatval($basePrice), "foilAvail" => intval(0), "foilPrice" => floatval(0));
			}
			
			if (sizeof($rows) < 50){
				echo "last page - ";
				break;
			}
		}
			
		//return;
		writeAndClose($codes[$i], $set);
	}
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


function logErrors(){
	echo "\n\n";

	if (!sizeof($GLOBALS["errors"])){echo "NO ERRORS !";}
	else {
		for ($i = 0; $i < sizeof($GLOBALS["errors"]); $i++){
			echo "error: ".$GLOBALS["errors"][$i]."\n";
		}
	}
}

?>
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
//die();
fetchAll($date);
$time += microtime(true);
echo "\n\n\n-".$fetch."-   Script Execution Completed; TIME:".round($time/60, 2)." minutes, fetch: ".$GLOBALS["cards"]." entries over ".$GLOBALS["requests"]." requests";


function fetchAll($date){

	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);
	
			
	$data = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);
	$codes = $data["codes"];
	$names = $data["names"];
	
	$db = DB::app();
	$toDo = $db->getSetsToPull($date);
		foreach ($toDo as $set){
		message("\n__NOW - ".$set["setname"]." / ".$set["setcode"].", id ".$set["id"].", foil ".$set["foil"].", nonfoil ".$set["nonfoil"]);
		$data;

		if ($set["type"] == 0){
			$pulled = crawlBaseSet($db, $date, $context, $set);
		}
		else if ($set["type"] == 1){
			$pulled = crawlGameBoxes($db, $date, $context, $set);
		}
		else if ($set["type"] == 2){
			$pulled = crawlFreeURL($db, $date, $context, $set);
		}


		if (!(writeAndClose($db, $set["setcode"], $date, $pulled))){
			message("error!"); die();
		}
	}
	die();





	crawl($date, $codes[0], $names[0], 1, 0, $context); // non foils
	crawl($date, $codes[1], $names[1], 1, 1, $context); // reg sets
	crawl($date, $codes[2], $names[2], 1, 0, $context); // promos
	//getSets($date, $context); // FTV sealed
	//getBoxPrices($date, $codes[4], $names[4], $context); // boxes
	
	logErrors();
}


function crawlBaseSet($db, $date, $context, $pull){

	$set = array();
	$exit = 0;
	$page = 1;
	$maxPages = 0;
	$prop = "data-original-title";

	while(!$exit){
		$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . doReplace($pull["setname"])."?onlyAvailable=on&sortBy=locName_asc&perSite=50";
		$url .= "&site=".$page;

		$html = file_get_html($url, false, $context); $GLOBALS["requests"]++;

		if (!$html){
			message("NO HTML ! ".$pull["setcode"]);
			sleep(5);
			$html = file_get_html($url, false, $context);

			if (!$html){message("still not!"); die();}
		}

		if (!$maxPages){
			$dropdown = $html->find("div.dropup > div.dropdown-menu", 0);
			$maxPages = $dropdown ? sizeof($dropdown->children()) : 1;
		}


		$rows = $html->find(".table-body", 0)->children();

		for ($k = 0; $k < sizeof($rows); $k++){
			$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->plaintext;
			//message("pulling row $k with name $name");
			$baseAvail = 0;
			$basePrice = 0.00;
			$foilAvail = 0;
			$foilPrice = 0.00;
			$rarity = "";
			
			if ($pull["foil"] && !$pull["nonfoil"]){
				$foilAvail = $rows[$k]->children(4)->children(0)->plaintext;
				$foilPrice = $rows[$k]->children(5)->plaintext;
				$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));
			}
			else {
				if ($pull["nonfoil"]){
					$baseAvail = $rows[$k]->children(4)->children(0)->plaintext;
					$basePrice = $rows[$k]->children(5)->plaintext;
					$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));
				}
				if ($pull["foil"]){
					$foilAvail = $rows[$k]->children(6)->plaintext;
					$foilPrice = $rows[$k]->children(7)->plaintext;
					$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));
				}
			}

			$rarity = substr($rows[$k]->children(3)->find(".icon", 0)->{$prop}, 0, 1);
			doAdd($name, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
		}
		
		$page++;

		if ($page >= $maxPages){
			break;
		}
		else if ($page >= 15){
			echo "ERROR \n\n";
			$GLOBALS["errors"][] = $pull["setcode"];
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}

function crawlGameBoxes($db, $date, $context, $pull){
	$game = substr($pull["setname"], 0, strlen($pull["setname"])-6);
	$set = array();	

	for ($j = 1; $j < 10; $j++){			
		$url = "https://www.cardmarket.com/en/".$game."/Products/Booster-Boxes?sortBy=name_asc&perSite=50";
		$url .= "&site=".$j;

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

			doAdd($name, "S", $baseAvail, $basePrice, intval(0), floatval(0), $set);
		}
		
		if (sizeof($rows) < 50){
			echo "last page - ";
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}

function crawlFreeURL($db, $date, $context, $pull){
	
	$set = array();	
	$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Sets?searchString=Sealed&sortBy=sellVolume_desc&perSite=50";
	
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
			doAdd($name, "S", $baseAvail, $basePrice, intval(0), floatval(0), $set);
		}
		
		if (sizeof($rows) < 50){
			echo "last page - ";
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}


function crawl($date, $codes, $names, $nonFoil, $foil, $context){
	
	//	$codes = array("RAM");
	//	$names = array("Ravnica Allegiance Mythic Edition");
	
	for ($i = 0; $i < sizeof($codes); $i++){

		echo "\n\n*** Beginning - ".$names[$i]." / ".$codes[$i]." $i "."\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $names[$i], "data" => array());

		$exit = 0;
		$page = 1;

		$prop = "data-original-title";

		$maxPages = 0;

		while(!$exit){
			$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . doReplace($names[$i])."?onlyAvailable=on&sortBy=locName_asc&perSite=50";
			$url .= "&site=".$page;
						
			$html = file_get_html($url, false, $context);
			$GLOBALS["requests"]++;

			if (!$html){
				message("NO HTML ! ".$codes[$i]."/".$i);
				sleep(5);
				$html = file_get_html($url, false, $context);

				if (!$html){message("still not!");die();}
			}

			if (!$maxPages){
				$dropdown = $html->find("div.dropup > div.dropdown-menu", 0);
				$maxPages = $dropdown ? sizeof($dropdown->children()) : 1;
			}


			$rows = $html->find(".table-body", 0)->children();

			for ($k = 0; $k < sizeof($rows); $k++){
				$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->plaintext;
				//echo $name."\n";
				$baseAvail = 0;
				$basePrice = 0.00;
				$foilAvail = 0;
				$foilPrice = 0.00;
				$rarity = "";
				
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

				$rarity = substr($rows[$k]->children(3)->find(".icon", 0)->{$prop}, 0, 1);

				doAdd($name, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
			}
			
			$page++;

			if ($page > $maxPages){
				break;
			}
			else if ($page >= 15){
				echo "ERROR \n\n";
				$GLOBALS["errors"][] = $codes[$i];
				break;
			}
		}
		//return;
		//die();
		writeAndClose($codes[$i], $set);
		$html->clear(); 
		unset($html);
		//die();
		//return;
	}
}

function getBoxPrices($date, $codes, $names, $context){
	
	for ($i = 0; $i < sizeof($names); $i++){

		$game = substr($names[$i], 0, strlen($names[$i])-6);
	
		$set = array("date" => $date, "code" => $codes[$i], "set" => $codes[$i]." Boxes", "data" => array());	
		
		for ($j = 1; $j < 10; $j++){			
		//	$url = "https://www.cardmarket.com/en/".$game."/Products/Booster-Boxes?mode=&searchString=&onlyAvailable=on&sortBy=locName_asc&perSite=50";
			$url = "https://www.cardmarket.com/en/".$game."/Products/Booster-Boxes?sortBy=locName_asc&perSite=50";
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

				doAdd($name, "S", $baseAvail, $basePrice, intval(0), floatval(0), $set);
			}
			
			if (sizeof($rows) < 50){
				echo "last page - ";
				break;
			}
		}
		writeAndClose($codes[$i], $set);
	}
}


function doAdd($cardname, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, &$set){
	$set[] = array(
		"cardname" => $cardname,
		"rarity" => $rarity,
		"baseAvail" => intval($baseAvail),
		"basePrice" => floatval($basePrice),
		"foilAvail" => intval($foilAvail),
		"foilPrice" => floatval($foilPrice)
	);
}
function doAddO($cardname, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, &$set){
	$set["data"][] = array(
		"cardname" => $cardname,
		"rarity" => $rarity,
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


?>
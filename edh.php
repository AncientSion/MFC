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
		message("\n__NOW - ".$set["setname"]." / ".$set["setcode"].", id ".$set["id"].", foil ".$set["foil"].", nonfoil ".$set["nonfoil"]);

		switch ($set["type"]){
			case 0: $pulled = crawlBaseSet($db, $context, $set); break;
			case 1: $pulled = crawlGameBoxes($db, $context, $set); break;
			case 2: $pulled = crawlFreeURL($db, $context, $set); break;
		}

		if (!(writeAndClose($db, $set["setcode"], $date, $pulled))){
			message("error on set ".$set["setcode"]); break;
		}
	}
	
	logErrors();
}


function crawlBaseSet($db, $context, $pull){

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

function crawlGameBoxes($db, $context, $pull){
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

function crawlFreeURL($db, $context, $pull){
	
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
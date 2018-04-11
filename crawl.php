<?php

include_once(__DIR__."\global.php");


//"header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36",

$context = stream_context_create(
    array(
        "http" =>
			array(
			    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T",
				"method" => "POST",
				"content" => http_build_query(
					array(
						"productFilter[idLanguage]" => array(1),
						"productFilter[isFoil]" => "Y",
						"productFilter[condition]" => array("NM", "EX")
					)
				)
			)
		)
);

$time = time();
$date = date('d.m.Y', $time);
$time = -microtime(true);

$data = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
$data = $data["codes"];

echo "Script Execution Started \n";



getFullFoilSets($date, $context, $data[0]);
getFullNonFoilSets($date, $context, $data[1]);
getNotCommonNotFoilSets($date, $context, $data[2]);
getStandSets($date, $context, $data[3]);
getMPSSets($date, $context, $data[4]);


$time += microtime(true);
echo "FINAL Script Execution Completed; TIME:".round($time/60, 2)." minutes.";
 

function getFullFoilSets($date, $context, $codes){
	$complete = array();

	for ($i = 0; $i < sizeof($codes); $i++){
	//for ($i = 0; $i < 1; $i++){
		$setJson = file_get_contents(__DIR__."/input/".$codes[$i].".json");
		$setData = json_decode($setJson, TRUE);

		$setName = $setData["mkm_name"];
		$cards = $setData["cards"];

		$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

		echo "\n\n*** Beginning - ".$setName." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $setName, "data" => array());
		$get = 0;

		
		for ($j = 0; $j < sizeof($cards); $j++){
		//for ($j = 223; $j < 224; $j++){
			if ($cards[$j]["rarity"][0] == "B"){continue;}
			$get++;
			echo "#".$get." - ".$cards[$j]["name"].", ".$cards[$j]["number"]."\n";
			$url = $baseUrl . urlencode($setName) . "/" . urlencode($cards[$j]["name"]);
			$html = file_get_html($url, false, $context);
			$table = $html->find(".availTable", 0);

			if (!$table){echo "___________________lacking TABLE CONTINUE \n"; continue;}

			$table = $table->children(0);
			$base = $table->children(0);
			$foil = $table->children(3);

			if (!$base || !$foil){echo "___________________lacking TR CONTINUE \n"; continue;}

			$baseAvail = $base->children(1)->children(0)->innertext;
			$basePrice = $table->children(1)->children(1)->children(0)->innertext;
			$basePrice = str_replace(",", ".", $basePrice);

			$foilAvail = $foil->children(1)->innertext;

			$offers = $html->find("#articlesTable", 0);
			$start = 0;
			if ($offers){
				$foilPrice = $offers->children(1)->children(5)->children(0)->children(0);
				if (sizeof($foilPrice->children())){
					$start = 6;
					$foilPrice = $foilPrice->children(0)->innertext;
				} else $foilPrice = $foilPrice->innertext;
			}
			else $foilPrice = $table->children(4)->children(1)->innertext;

			$foilPrice = str_replace(",", ".", substr($foilPrice, $start, strpos($foilPrice, " ")));


			//echo "price:".$foilPrice."\n";

			//echo "cheapest: ".$foilPrice."\n";
			$set["data"][] = array(
				"name" => $cards[$j]["name"], 
				"rarity" => $cards[$j]["rarity"], 
				"baseAvail" => intval($baseAvail),
				"basePrice" => floatval($basePrice),
				"foilAvail" => intval($foilAvail),
				"foilPrice" => floatval($foilPrice)
			);
		}
		writeAndClose($codes[$i], $set);
	}
}

function getFullNonFoilSets($date, $context, $codes){
	$sub = time();
	$sub = -microtime(true);

	echo "Script Execution Started \n";

	$complete = array();

	for ($i = 0; $i < sizeof($codes); $i++){
	//for ($i = 0; $i < 2; $i++){
		$setJson = file_get_contents(__DIR__."/input/".$codes[$i].".json");
		$setData = json_decode($setJson, TRUE);

		$setName = $setData["mkm_name"];
		$cards = $setData["cards"];

		$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

		echo "\n\n*** Beginning - ".$setName." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $setName, "data" => array());
		$get = 0;
		for ($j = 0; $j < sizeof($cards); $j++){
		//for ($j = 0; $j < 2; $j++){
			if ($cards[$j]["rarity"][0] == "B"){continue;}
			$get++;
			echo "#".$get." - ".$cards[$j]["name"]."\n";
			$url = $baseUrl . urlencode($setName) . "/" . urlencode($cards[$j]["name"]);
			$html = file_get_html($url, false, $context);
			$table = $html->find(".availTable", 0);
			//echo $table;

			if (!$table){echo "lacking TABLE CONTINUE \n"; continue;}

			$availTR = $table->children(0)->children(0);
			$priceTR = $table->children(0)->children(1);

			if (!$availTR){echo "NO STOCK \n"; continue;}
			if (!$priceTR){echo "NO PRICE \n"; continue;}

			$availTR = $availTR->children(1)->children(0)->innertext;
			$priceTR = $priceTR->children(1)->children(0)->innertext;

			$basePrice = str_replace(",", ".", $priceTR);


			$set["data"][] = array(
				"name" => $cards[$j]["name"], 
				"rarity" => $cards[$j]["rarity"], 
				"baseAvail" => intval($availTR),
				"basePrice" => floatval($basePrice),
				"foilAvail" => intval(0),
				"foilPrice" => floatval(0)
			);
		}
		writeAndClose($codes[$i], $set);
	}

	$sub += microtime(true);
	echo "Script Execution Completed; TIME:".round($sub)." seconds.";
 
}

function getNotCommonNotFoilSets($date, $context, $codes){
	$sub = time();
	$sub = -microtime(true);

	echo "Script Execution Started \n";
	$complete = array();

	for ($i = 0; $i < sizeof($codes); $i++){
	//for ($i = 0; $i < 2; $i++){
		$setJson = file_get_contents(__DIR__."/input/".$codes[$i].".json");
		$setData = json_decode($setJson, TRUE);

		$setName = $setData["mkm_name"];
		$cards = $setData["cards"];

		$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

		echo "\n\n*** Beginning - ".$setName." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $setName, "data" => array());
		$get = 0;

		for ($j = 0; $j < sizeof($cards); $j++){
		//for ($j = 0; $j < 2; $j++){
			if (($cards[$j]["rarity"][0] == "C" || $cards[$j]["rarity"][0] == "B")){continue;}
			$get++;

			echo "#".$get." - ".$cards[$j]["name"]."\n";
			$url = $baseUrl . urlencode($setName) . "/" . urlencode($cards[$j]["name"]);
			$html = file_get_html($url, false, $context);
			$table = $html->find(".availTable", 0);
			//echo $table;

			if (!$table){echo "lacking TABLE CONTINUE \n"; continue;}

			$availTR = $table->children(0)->children(0);
			$priceTR = $table->children(0)->children(1);

			if (!$availTR){echo "NO STOCK \n"; continue;}
			if (!$priceTR){echo "NO PRICE \n"; continue;}

			$availTR = $availTR->children(1)->children(0)->innertext;
			$priceTR = $priceTR->children(1)->children(0)->innertext;

			$basePrice = str_replace(",", ".", $priceTR);


			$set["data"][] = array(
				"name" => $cards[$j]["name"], 
				"rarity" => $cards[$j]["rarity"], 
				"baseAvail" => intval($availTR),
				"basePrice" => floatval($basePrice),
				"foilAvail" => intval(0),
				"foilPrice" => floatval(0)
			);
		}
		writeAndClose($codes[$i], $set);
	}

	$sub += microtime(true);
	echo "Script Execution Completed; TIME:".round($sub)." seconds.";
}

function getStandSets($date, $context, $codes){
	$sub = time();
	$sub = -microtime(true);

	echo "Script Execution Started \n";
	$complete = array();

	//for ($i = 0; $i < 1; $i++){
	for ($i = 0; $i < sizeof($codes); $i++){
		$setJson = file_get_contents(__DIR__."/input/".$codes[$i].".json");
		$setData = json_decode($setJson, TRUE);

		$setName = $setData["mkm_name"];
		$cards = $setData["cards"];

		$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

		echo "\n\n*** Beginning - ".$setName." / ".$setData["code"]." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $setName, "data" => array());
		$get = 0;

		//for ($j = 0; $j < 2; $j++){
		for ($j = 0; $j < sizeof($cards); $j++){
			if (($cards[$j]["rarity"][0] == "C" || $cards[$j]["rarity"][0] == "B")){continue;}
			if (($cards[$j]["layout"][0] != "n")){echo "Skipping Special: ".$cards[$j]["name"]."\n"; continue;}
			$get++;
			echo "#".$get." - ".$cards[$j]["name"].", ".$cards[$j]["number"]."\n";
			$url = $baseUrl . urlencode($setName) . "/" . urlencode($cards[$j]["name"]);
			$html = file_get_html($url, false, $context);
			$table = $html->find(".availTable", 0);

			if (!$table){echo "___________________lacking TABLE CONTINUE \n"; continue;}

			$table = $table->children(0);
			$base = $table->children(0);
			$foil = $table->children(3);

			if (!$base || !$foil){echo "___________________lacking TR CONTINUE \n"; continue;}

			$baseAvail = $base->children(1)->children(0)->innertext;
			$basePrice = $table->children(1)->children(1)->children(0)->innertext;
			$basePrice = str_replace(",", ".", $basePrice);

			$foilAvail = $foil->children(1)->innertext;
			$offers = $html->find("#articlesTable", 0);
			$start = 0;
			if ($offers){
				$foilPrice = $offers->children(1)->children(5)->children(0)->children(0);
				if (sizeof($foilPrice->children())){
					$start = 6;
					$foilPrice = $foilPrice->children(0)->innertext;
				} else $foilPrice = $foilPrice->innertext;
			}
			else $foilPrice = $table->children(4)->children(1)->innertext;

			$foilPrice = str_replace(",", ".", substr($foilPrice, $start, strpos($foilPrice, " ")));

			$set["data"][] = array(
				"name" => $cards[$j]["name"], 
				"rarity" => $cards[$j]["rarity"], 
				"baseAvail" => intval($baseAvail),
				"basePrice" => floatval($basePrice),
				"foilAvail" => intval($foilAvail),
				"foilPrice" => floatval($foilPrice)
			);			
		}
		writeAndClose($codes[$i], $set);
	}

	$sub += microtime(true);
	echo "Script Execution Completed; TIME:".round($sub)." seconds.";
}

function getMPSSets($date, $context, $codes){
	$sub = time();
	$sub = -microtime(true);

	echo "Script Execution Started \n";
	$complete = array();

	//for ($i = 0; $i < 1; $i++){
	for ($i = 0; $i < sizeof($codes); $i++){
		$setJson = file_get_contents(__DIR__."/input/".$codes[$i].".json");
		$setData = json_decode($setJson, TRUE);

		$setName = $setData["mkm_name"];
		$cards = $setData["cards"];

		$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

		echo "\n\n*** Beginning - ".$setName." / ".$date."***\n";

		$set = array("date" => $date, "code" => $codes[$i], "set" => $setName, "data" => array());
		$get = 0;

		//for ($j = 0; $j < 1; $j++){
		for ($j = 0; $j < sizeof($cards); $j++){
			$get++;
			echo "#".$get." - ".$cards[$j]["name"].", ".$cards[$j]["number"]."\n";
			$url = $baseUrl . urlencode($setName) . "/" . urlencode($cards[$j]["name"]);
			$html = file_get_html($url, false, $context);
			$box = $html->find(".availTable", 0);

			if (!$box){echo "___________________lacking TABLE CONTINUE \n"; continue;}

			$table = $box->children(0);
			$foilAmount = $table->children(0)->children(1)->children(0)->innertext;
			$foilPrice = str_replace(",", ".", $table->children(1)->children(1)->children(0)->innertext);

			$set["data"][] = array(
				"name" => $cards[$j]["name"], 
				"rarity" => $cards[$j]["rarity"], 
				"baseAvail" => intval(0),
				"basePrice" => floatval(0),
				"foilAvail" => intval($foilAmount),
				"foilPrice" => floatval($foilPrice)
			);

		}
		writeAndClose($codes[$i], $set);
	}

	$sub += microtime(true);
	echo "Script Execution Completed; TIME:".round($sub)." seconds.";
}


function writeAndClose($code, $data){
	echo "Writing ".$code."\n";
	//$file = fopen(__DIR__."/output/" . $code .".json", "a");
	$file = fopen(__DIR__."/output/" . $code .".json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($data)."\n"."]}");
	fclose($file);
}


?>
<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



function requestShakers($codes, $includes, $foil, $depth, $minPrice, $maxPrice, $availChange, $compareType){
	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);

	$names = getSetNamesByCodes($codes);

	/*
	$depth = 10;
	$foil = 1;
	$minPrice = 0.5;
	$maxPrice = 0;
	$availChange = -0.0;
	$includes = array("C", "R", "M", "S");
	$compareType = "pct";
	*/

	$html = "";

	/*
	$html .="Delving: ".$depth." days of data, ";
	$html .="only ".$foil."</br>";
	$html .="Price NOW > ".$minPrice.", ";
	$html .="Price NOW < ".$maxPrice."</br>";
	$html .="Supply Avail Change > ".$availChange." (type: ".$compareType.")</br>";
	*/
	$allSets = array();

	//$codes = array(array("A25"));

	for ($i = 0; $i < sizeof($codes); $i++){
		//for ($j = 0; $j < sizeof($codes[$i]); $j++){
		//for ($i = 0; $i < 1; $i++){
		//	for ($j = 0; $j < 1; $j++){
			$setName = $names[$i];


			//$html .="Preparing ".$setName." prices</br>";


			$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i].".json"), TRUE);
			$cards = $cards["cards"];
			$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i].".json"), TRUE);
			$cards = $cards["content"];
			if (!$points){$html .="</br></br>No data found for:".$setName; continue;}
			$extract = array(
				"set" => $setName,
				"code" => $codes[$i],
				"compareDate" => $points[max(0, (sizeof($points)-1 -$depth))]["date"],
				"lastDate" => $points[sizeof($points)-1]["date"],
				"shakers" => array()
			);

			for ($k = 0; $k < sizeof($cards); $k++){

				$skip = true;
				for ($l = 0; $l < sizeof($includes); $l++){
					if ($cards[$k]["rarity"][0] == $includes[$l]){$skip = false; break;}
				}

				if ($skip){continue;}

				$name = $cards[$k]["name"];
				$last = getCardDataSet($name, $points[sizeof($points)-1]["data"]);
				if (!$last){continue;}
				if ($minPrice != 0 && $last["foilPrice"] < $minPrice){continue;}
				if ($maxPrice != 0 && $last["foilPrice"] > $maxPrice){continue;}
				$card = array(
					"name" => $name,
					"rarity" => $cards[$k]["rarity"],
					"baseAvail" => array(),
					"basePrice" => array(),
					"foilAvail" => array(),
					"foilPrice" => array(),
					"baseAvailChange" => array(),
					"basePriceChange" => array(),
					"foilAvailChange" => array(),
					"foilPriceChange" => array()
				);


				for ($l = sizeof($points)-1; $l >= max(0, (sizeof($points)-1 -$depth)); $l--){
					addCardDataPoint($card, getCardDataSet($name, $points[$l]["data"]));
				}
				$extract["shakers"][] = $card;
			}

			if (!sizeof($extract["shakers"])){continue;}
			$allSets[] = $extract;
	}


	for ($i = 0; $i < sizeof($allSets); $i++){
		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			setChangeValue($allSets[$i]["shakers"][$j], "baseAvail");
			setChangeValue($allSets[$i]["shakers"][$j], "basePrice");
			setChangeValue($allSets[$i]["shakers"][$j], "foilAvail");
			setChangeValue($allSets[$i]["shakers"][$j], "foilPrice");
		}
	}
	
	echo $html;
	echo buildTables($allSets, $foil, $compareType, $availChange, $minPrice);

}


?>
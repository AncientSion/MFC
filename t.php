<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



function getCardDataSet($name, $data){
	for ($i = 0; $i < sizeof($data); $i++){
		if ($data[$i]["name"] == $name){
			return $data[$i];
		}
	}
	return false;
}

function addCardDataPoint(&$currentSet, $point){
	//echo "</br>adding point</br>";
	//var_export($point);
	//echo "</br>";

	if (!isset($point["basePrice"])){$point["basePrice"] = 0;}//echo $point["name"];}
	if (!isset($point["foilPrice"])){$point["foilPrice"] = 0;}//echo $point["name"];}
	$currentSet["baseAvail"][] = $point["baseAvail"];
	$currentSet["basePrice"][] = $point["basePrice"];
	$currentSet["foilAvail"][] = $point["foilAvail"];
	$currentSet["foilPrice"][] = $point["foilPrice"];

	//echo "</br>current set</br>";
	//var_Export($currentSet);
}


function getMemory(){
	$size = memory_get_usage(true);
    $unit = array('b','kb','mb','gb','tb','pb');
    echo (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i])."</br>";
}
	
function buildFullCardPool(){
	$sets = file_get_contents(__DIR__."/input/sets.json");
	$sets = json_decode($sets);

	$data = array();

	for ($i = 0; $i < sizeof($sets->codes); $i++){
		for ($j = 0; $j < sizeof($sets->codes[$i]); $j++){
			echo "adding set: ".$sets->codes[$i][$j]."\n";
			$json = json_decode(file_get_contents(__DIR__."/input/".$sets->codes[$i][$j].".json"));

			$set = array("code" => $json->code, "name" => $json->name, "cards" => array());
			foreach ($json->cards as $card){
				$set["cards"][] = array("name" => $card->name, "rarity" => $card->rarity);
			}
			$data[] = $set;
		}
	}

	$file = fopen(__DIR__."/output/cardlist.json", "r");
	fseek($file, 0);
	fwrite($file, json_encode($data));
	fclose($file);
}



function getSetNamesByCodes($data){
	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);

	//var_export($data);
	//return;

	//echo "s: ".sizeof($data);

	$codes = $sets["codes"];
	$names = $sets["names"];
	$return = array();

	for ($i = 0; $i < sizeof($data); $i++){
		for ($j = 0; $j < sizeof($codes); $j++){
			for ($k = 0; $k < sizeof($codes[$j]); $k++){
				if ($data[$i] == $codes[$j][$k]){
					$return[] = $names[$j][$k]; break 2;
				}
			}
		}
	}

	return $return;
}


function getForm($get){
	//var_export($get);
	$html = "";

	$html .="<form method='get'>";

	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["codes"];

	$rarityStr = array("Common", "Uncommon", "Rare", "Mythic Rare", "Special");
	$rarity = array("C", "U", "R", "M", "S");
	$preset = array('', '', "checked='checked'", "checked='checked'", "checked='checked'");

	$html .="<div class='checkWrapper'>";
	$html .="<div id='rarity' class='toggle'>Rarities to include (right-click, toggle all)</div>";


	if (sizeof($get) && $get["rarities"]){
		for ($i = 0; $i < sizeof($rarity); $i++){
			$checked = '';
			for ($j = 0; $j < sizeof($get["rarities"]); $j++){
				if ($get["rarities"][$j] == $rarity[$i]){
					$checked = "checked='checked'";
				}
			}

			$html .="<div class='checkContainer'>";
			$html .="<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' ".$checked.">";
			$html .="<span>".$rarityStr[$i]."</br>";
			$html .="</div>";
		}
	}
	else {
		for ($i = 0; $i < sizeof($rarity); $i++){
			$html .="<div class='checkContainer'>";
			$html .="<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' ".$preset[$i].">";
			$html .="<span>".$rarityStr[$i]."</br>";
			$html .="</div>";
		} 
	}
	$html .="</div>"; 

	$foilChecked = "";
	$nonFoilChecked = "";
	if (sizeof($get)){
		if ($get["foil"] == "Foil"){$foilChecked = "checked='checked'";}
		else if ($get["foil"] == "Non Foil"){$nonFoilChecked = "checked='checked'";}
	}
	else {
		$foilChecked = "checked='checked'";
	}

	$html .="<div class='checkWrapper'>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Foil'".$foilChecked."'>Foil</div>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Non Foil'".$nonFoilChecked."'>Non Foil</div>";
	//$html .= "</div>";

	//$html .="<div class='checkWrapper'>";

	$depth = 1;
	if (sizeof($get)){$depth = $get["depth"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>DAYS</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='1' max='50' value='".$depth."' name='depth'>";
	$html .= "</div>";
	$html .= "</div>";

	$minPrice = 3;
	if (sizeof($get)){$minPrice = $get["minPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='minPrice'>Min € (now)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='0' max='5000' value='".$minPrice."' name='minPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxPrice = 100;
	if (sizeof($get)){$maxPrice = $get["maxPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='maxPrice'>Max € (now)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number'min='0' max='5000' value='".$maxPrice."' name='maxPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$availChange = -3;
	if (sizeof($get)){$availChange = $get["availChange"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='availChange'>Supply Change</div>";
	$html .="<div class=''>";
	$html .= "<input type='number'min='-100' max='100' value='".$availChange."' name='availChange'>";
	$html .= "</div>";
	$html .= "</div>";


	$pctChecked = "";
	$absChecked = "";
	if (sizeof($get)){
		if ($get["compareType"] == "PCT"){$pctChecked = "checked='checked'";}
		else if ($get["compareType"] == "ABS"){$absChecked = "checked='checked'";}
	}
	else {
		$pctChecked = "checked='checked'"; 
	}

	$html .="<div class='inputContainer'>";
	$html .="<div class='checkContainer'><input type='radio' name='compareType' value='PCT'".$pctChecked."'>%-based</div>";
	$html .="<div class='checkContainer'><input type='radio' name='compareType' value='ABS'".$absChecked."'>abs-based</div>";
	$html .= "</div>";



	$html .= "</div>";

	$html .="<div class='checkWrapper'>";
	$html .="<div id='set' class='toggle'>Sets to include (right-click, toggle all)</div>";
	for ($i = 0; $i < sizeof($codes); $i++){
		for ($j = 0; $j < sizeof($codes[$i]); $j++){

			$checked = '';
			if (sizeof($get) && $get["sets"]){
				for ($k = 0; $k < sizeof($get["sets"]); $k++){
					if ($get["sets"][$k] == $codes[$i][$j]){
						$checked = "checked='checked'";
					}
				}
			}

			$html .="<div class='checkContainer'><input type='checkbox' name='sets[]' value='".$codes[$i][$j]."' ".$checked.">";
			$html .="<span>".$codes[$i][$j]."</span>";
			$html .="</div>";
		}
		//$html .="</br>";
	}

	$html .="</div>";
	$html .="</br>";
	$html .="<input type='submit' style='width: 200px; font-size: 26px' value='Search'></input>";
	$html .="</form>";

	return $html;
}



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


			$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i].".json"), TRUE)["cards"];
			$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i].".json"), TRUE)["content"];
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
		//}
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
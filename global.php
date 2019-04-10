<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__."/simple_html_dom.php");



function getCardDataSet($name, $data){
	for ($i = 0; $i < sizeof($data); $i++){
		if ($data[$i]["name"] == $name){
			return $data[$i];
		}
	}
	return false;
}

function getMKMURL($set, $card){

	//echo $card;
	//echo "</br>";
	
	if (strlen($set) > 5 && substr($set, strlen($set)-5, 5) == "Boxes"){
		$base = "https://www.cardmarket.com/en/".substr($set, 0, strlen($set)-6)."/Products/Booster+Boxes/";
		$card = str_replace("-//", "", preg_replace("/ /", "-", preg_replace("/'/", "", preg_replace("/,/", "", $card))));
		return $base.$card;
	}
	else {
		$base = "https://www.cardmarket.com/en/Magic/Products/Singles/";
		$set =  doReplace($set);
		$card = str_replace("-/-", "-", str_replace("-//", "", preg_replace("/ /", "-", preg_replace("/'/", "-", preg_replace("/,/", "", $card)))));
		//echo $card."</br>";
		$url = $base.$set."/".$card;
		return $url;
	}
}


function doReplace($set){
	return preg_replace("/ /", "-", preg_replace("/'/", "", preg_replace("/,/", "", $set)));
}

function addCardDataPoint(&$currentSet, $point){
	//echo "</br>adding point</br>";
	//var_export($point); echo "</br>";

	if (!isset($point["baseAvail"])){$point["baseAvail"] = 0;}
	if (!isset($point["basePrice"])){$point["basePrice"] = 0;}//echo $point["name"];}
	if (!isset($point["foilAvail"])){$point["foilAvail"] = 0;}//echo $point["name"];}
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
    return (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]);
}
	
function buildFullCardPool(){
	$sets = file_get_contents(__DIR__."/output/avail.json");
	$sets = json_decode($sets);

	$data = array();	
	
	for ($i = 3; $i < sizeof($sets->codes); $i++){		
		for ($j = 0; $j < sizeof($sets->codes[$i]); $j++){	
			echo "adding set: ".$sets->codes[$i][$j]."</br>";
			$set = array("code" => strtoupper($sets->codes[$i][$j]), "name" => $sets->names[$i][$j], "cards" => array());			
			$json = json_decode(file_get_contents(__DIR__."/output/".$sets->codes[$i][$j].".json"));
			
			foreach ($json->content[sizeof($json->content)-1]->data as $card){
				$set["cards"][] = array("name" => $card->name, "rarity" => "S");
			}
			$data[] = $set;	
		}
	}

	for ($i = 0; $i < sizeof($sets->codes)-2; $i++){
		for ($j = 0; $j < sizeof($sets->codes[$i]); $j++){
			echo "adding set: ".$sets->codes[$i][$j]."</br>";
			$json = json_decode(file_get_contents(__DIR__."/input/".$sets->codes[$i][$j].".json"));

			$set = array("code" => $json->code, "name" => $json->name, "cards" => array());	
				
			$skipNext = 0;
				
			foreach ($json->cards as $card){
				$name = $card->name; 
				//echo $name."</br>";
				
				if ($skipNext){
					$skipNext = 0;
					//echo "SKIPPING</br>";
					continue;
				}
					
				if (isset($card->layout)){
					if ($card->layout == "double-faced"){
						$name = $card->names[0]. " / ".$card->names[1];
						$skipNext = 1;
						//echo $name."</br>";
					}
					else if ($card->layout == "split"){
						$name = $card->names[0]. " // ".$card->names[1];
						$skipNext = 1;
						//echo $name."</br>";
					}
				}
				
				$set["cards"][] = array("name" => $name, "rarity" => strtoupper($card->rarity[0]));
				
			}
			$data[] = $set;
		}
	}

	$file = fopen(__DIR__."/output/cardlist.json", "a");
	echo "writing total of ".sizeof($data)." sets</br>";
	fwrite($file, json_encode($data));
	fclose($file);
}



function getSetNamesByCodes($data){
	$sets = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);

	//var_export($data); return;

	$codes = $sets["codes"];
	$names = $sets["names"];
	$return = array();
	
	//var_export($codes); return;

	for ($i = 0; $i < sizeof($data); $i++){
		for ($j = 0; $j < sizeof($codes); $j++){
			for ($k = 0; $k < sizeof($codes[$j]); $k++){
				if ($data[$i] == $codes[$j][$k]){
					$return[] = $names[$j][$k]; break 2;
				}
			}
		}
	}	
	///var_export($return); return;
	return $return;
}


function getForm($get){
	//var_export($get);
	$html = "";

	$html .="<form class='upper' method='get'>"; 

	$foilChecked = "";
	$nonFoilChecked = "";
	if (sizeof($get)){
		if ($get["foil"] == "Is Foil"){$foilChecked = "checked='checked'";}
		else if ($get["foil"] == "Not Foil"){$nonFoilChecked = "checked='checked'";}
	}
	else {
		$foilChecked = "checked='checked'";
	}

	$html .="<div class='checkWrapper'>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Is Foil'".$foilChecked."'>Is Foil</div>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Not Foil'".$nonFoilChecked."'>Not Foil</div>";


	
	
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



	$depth = 1;
	if (sizeof($get)){$depth = $get["depth"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Depth</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='-999' max='999' value='".$depth."' name='depth'>";
	$html .= "</div>";
	$html .= "</div>";

	$minAvail = 0;
	if (sizeof($get)){$minAvail = $get["minAvail"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Min Avail (n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='0' max='10000' value='".$minAvail."' name='minAvail'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxAvail = 0;
	if (sizeof($get)){$maxAvail = $get["maxAvail"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Max Avail (n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=10000 value='".$maxAvail."' name='maxAvail'>";
	$html .= "</div>";
	$html .= "</div>";

	$minPrice = 0;
	if (sizeof($get)){$minPrice = $get["minPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='minPrice'>Min (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=5000 step=0.5 value='".$minPrice."' name='minPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxPrice = 0;
	if (sizeof($get)){$maxPrice = $get["maxPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='maxPrice'>Max (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=5000 value='".$maxPrice."' name='maxPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$availChange = -14;
	if (sizeof($get)){$availChange = $get["availChange"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='availChange'>Supply Change</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=-100 max=100 value='".$availChange."' name='availChange'>";
	$html .= "</div>";
	$html .= "</div>";

	
	$checked = "";
	if (sizeof($get) && isset($get["plusminus"])){
		$checked = "checked='checked'";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='plusminus' value='1'".$checked."'>";
	$html .= "+/-</div>";
	
	$stackDisplay = "";
	$checked = "";
	$value = "";
	//var_export($get);
	if (sizeof($get) && isset($get["stackDisplay"])){
		$checked = "checked='checked'";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='stackDisplay' value='1'".$checked."'>";
	$html .= "stack</div>";
	
	
	$skipUnchanged = "";
	$checked = "";
	$value = "";
	//var_export($get);
	if (sizeof($get) && isset($get["skipUnchanged"])){
		$checked = "checked='checked'";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='skipUnchanged' value='1'".$checked."'>";
	$html .= "Skip 0</div>";



	$html .= "</div>";

	$html .="<div class='checkWrapper'>";
	//$html .="<div id='set' class='toggle'>Sets to include</div>";



	$sets = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["codes"];

	//var_export($codes);
	//echo "</br></br>";
	//var_export($get["sets"]);

	for ($i = 0; $i < sizeof($codes); $i++){
		$html .= "<div class='setDivider'>";
		for ($j = 0; $j < sizeof($codes[$i]); $j++){

			$checked = "";
			//$checked = "checked='checked'";
			if (sizeof($get) && $get["sets"]){
				for ($k = 0; $k < sizeof($get["sets"]); $k++){
					if ($get["sets"][$k] == $codes[$i][$j]){
						$checked = "checked='checked'";
					}
				}
			}

			$html .="<div class='checkContainer set'><input type='checkbox' name='sets[]' value='".$codes[$i][$j]."' ".$checked.">";
			$html .="<span>".$codes[$i][$j]."</span>";
			$html .="</div>";
		}
		$html .= "</div>";
	}
	$html .="</div>";



	$rarity = array("C", "U", "R", "M", "S");
	$preset = array('', '', "checked='checked'", "checked='checked'", "checked='checked'");
	$html .="<div class='checkWrapper'>";

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
			$html .="<span>".$rarity[$i]."</br>";
			$html .="</div>";
		}
	}
	else {
		for ($i = 0; $i < sizeof($rarity); $i++){
			$html .="<div class='checkContainer'>";
			$html .="<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' ".$preset[$i].">";
			$html .="<span>".$rarity[$i]."</br>";
			$html .="</div>";
		} 
	}
	
	$html .="<div style='display: inline-block'><input type='submit' style='width: 100px; font-size: 20px' value='Search'></input></div>";
	$html .="</div>";
	$html .="</form>";

	$html .="<div class='upper'>";
	$html .='<div class="lower"><a href="shakers.php">Reload Blank</a></div>';
	$html .='<div class="lower"><a href="charts.php">Go to single card lookup</a></div>';
	$html .='<div class="lower"><input id="toggleVis" type="button" value="hide"></div>';
	$html .="</div>";

	return $html;
}



function writeAllBoxes(){
	$data =  file_get_contents(__DIR__."/output/avail.json");
	$data = json_decode($data);
	
	for ($i = 0; $i < sizeof($data->codes[3]); $i++){
		convertBoosterInput($data->names[3][$i], $data->codes[3][$i]);
		return;
	}
}


function convertBoosterInput($name, $code){
	$set = array(
		"name" => $name,
		"cards" => array(),
	);

	$data =  file_get_contents(__DIR__."/mkm/input/".$code.".json");
	$data = json_decode($data);

	foreach ($entry as $box){
		//echo "day: ".$day->date.": ".sizeof($day->data)."\n";
		$set["cards"][] = array("name" => $box->name, "rarity" => "D");
	}

	$file = fopen(__DIR__."/input/".$code.".json", "a");
	echo "writing \n";
	fwrite($file, json_encode($set));
	fclose($file);
}

function debug($string){
	//echo $string;
	file_put_contents(__DIR__."/debug.log", $string.PHP_EOL, FILE_APPEND);
}

function logShakers($codes, $includes, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChange, $plusminus, $stackDisplay, $skipUnchanged, $compareType){
	return;
	$stamp = time();

	$search = array(
		"type" => "compare",
		"stamp" => $stamp,
		"date" => date('d.m.Y', $stamp),
		"time" => date('H:i:s', $stamp),
		"options" => array(
			$codes, $includes, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChange, $plusminus, $stackDisplay, $skipUnchanged, $compareType
		)
	);

	file_put_contents(__DIR__."/search.log", json_encode($search, JSON_NUMERIC_CHECK).",\n", FILE_APPEND);
}

function logChart($set, $card){
	return;
	$stamp = time();

	$search = array(
		"type" => "chart",
		"stamp" => $stamp,
		"date" => date('d.m.Y', $stamp),
		"time" => date('H:i:s', $stamp),
		"set" => $set,
		"card" => $card
	);

	file_put_contents(__DIR__."/search.log", json_encode($search, JSON_NUMERIC_CHECK).",\n", FILE_APPEND);
}



function requestShakers($codes, $includes, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChange, $plusminus, $stackDisplay, $skipUnchanged, $compareType){

	$time = time();
	$date = date('d.m.Y', $time);
	$time = -microtime(true);
	
	//mored Ascensecho var_export(func_get_args());

	logShakers($codes, $includes, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChange, $plusminus, $stackDisplay, $skipUnchanged, $compareType);
	
	$sets = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);

	$names = getSetNamesByCodes($codes);
	$allSets = array();
	
	$cardList = json_decode(file_get_contents(__DIR__."/output/cardlist.json"), TRUE);
		
	for ($i = 0; $i < sizeof($codes); $i++){
		$setName = $names[$i];
		
		$cards;
		for ($j = 0; $j < sizeof($cardList); $j++){
			if (strtoupper($cardList[$j]["code"]) == $codes[$i]){
				$cards = $cardList[$j]["cards"];
				break;
			}
		}
		
		//var_export($cards);
		
		$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i].".json"), TRUE);
		$points = $points["content"];
		if (!$points){continue;}
		
		$delve = $depth;
		if ($delve >= 0){
			$delve = max(0, (sizeof($points)-1 -$depth));
		}
		else if ($delve < 0){
			$delve = -$depth;
		}
		else {
			$delve = 0;
		}
	
	
		$extract = array(
			"set" => $setName,
			"code" => $codes[$i],
			"compareDate" => $points[$delve]["date"],
			"lastDate" => $points[sizeof($points)-1]["date"],
			"shakers" => array()
		);

		//var_export($extract);
		
		for ($k = 0; $k < sizeof($cards); $k++){

			//var_export($cards[$k]); echo "</br>";
			//echo $cards[$k]["name"]."</br>";
			$skip = true;
			for ($l = 0; $l < sizeof($includes); $l++){
				if ($cards[$k]["rarity"] == $includes[$l]){$skip = false; break;}
			}

			if ($skip){continue;}
			
			if (!(isset($cards[$k]["name"]))){
				echo "error: ".$codes[$i];
			}

			$name = $cards[$k]["name"];
			$last = getCardDataSet($name, $points[sizeof($points)-1]["data"]);
			if (!$last){continue;}
			if ($minAvail && ((!$foil && $last["baseAvail"] < $minAvail) || ($foil && $last["foilAvail"] < $minAvail))){continue;}
			if ($maxAvail && ((!$foil && $last["baseAvail"] > $maxAvail) || ($foil && $last["foilAvail"] > $maxAvail))){continue;}
			
			if ($minPrice && ((!$foil && $last["basePrice"] < $minPrice) || ($foil && $last["foilPrice"] < $minPrice))){continue;}
			if ($maxPrice && ((!$foil && $last["basePrice"] > $maxPrice) || ($foil && $last["foilPrice"] > $maxPrice))){continue;}
			
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

			$amountData = sizeof($points)-1;
			
			for ($l = $amountData; $l >= $delve; $l--){
				$dataPoint = getCardDataSet($name, $points[$l]["data"]);
				if (!$dataPoint){break;}
				addCardDataPoint($card, $dataPoint);
			}
			$extract["shakers"][] = $card;
		}

		if (!sizeof($extract["shakers"])){continue;}
		$allSets[] = $extract;
	}


	for ($i = 0; $i < sizeof($allSets); $i++){
		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			setChangeValue($allSets[$i]["shakers"][$j], $foil);
		}
	}
	
	$stackDisplay = ($stackDisplay && $depth && $depth < 11);

	$time += microtime(true);
	debug("Script Execution Completed; TIME:".round($time, 2)." seconds, memory: ".getMemory());


	echo buildTables($allSets, $foil, $compareType, $availChange, $minPrice, $plusminus, $stackDisplay, $skipUnchanged);

}

function setChangeValue(&$card, $forFoil){
	$props = array();
	
	if (!$forFoil){
		$props = array("baseAvail", "basePrice");
	} else $props = array("foilAvail", "foilPrice");
		
	for ($i = 0; $i < sizeof($props); $i++){	
		if ($card[$props[$i]][sizeof($card[$props[$i]])-1] == 0){
			$card[$props[$i]."Change"][0] = 0; $card[$props[$i]."Change"][1] = 0;
			return;
		}
		$card[$props[$i]."Change"][0] = round($card[$props[$i]][0] - $card[$props[$i]][sizeof($card[$props[$i]])-1], 2); 
		$card[$props[$i]."Change"][1] = round((($card[$props[$i]][0] / $card[$props[$i]][sizeof($card[$props[$i]])-1])*100)-100, 2);
	}
	//var_export(func_get_args()); die();
}


function buildTables($allSets, $foil, $compareType, $availChange, $minPrice, $plusminus, $stackDisplay, $skipUnchanged){

	//echo $availChange;
	/*var_export(func_get_arg(1));
			echo "</br></br>";
	var_export(func_get_arg(2));
			echo "</br></br>";
	var_export(func_get_arg(3));
			echo "</br></br>";
	var_export(func_get_arg(4));
			echo "</br></br>";
	*/
	$avail = "";
	$change = "";
	$price = "";
	$html = "";
	$html .= "</br>";
	$index;

	if ($foil == "Non Foil"){
		$avail = "baseAvail";
		$price = "basePrice";
		$volChange = "baseAvailChange";
		$moneyChange = "basePriceChange";
	}
	else {
		$avail = "foilAvail";
		$price = "foilPrice";
		$volChange = "foilAvailChange";
		$moneyChange = "foilPriceChange";
	}

	if ($compareType == "ABS"){
		$index = 0;
	} else $index = 1;

	$colSpan = 9;
	if ($stackDisplay){$colSpan = 10;}
	

	for ($i = 0; $i < sizeof($allSets); $i++){
		$html .="<table class='moveTable' style='width: 1100px'>";

		$html .="<thead>";
		$html .="<tr><th class='set' colSpan=".$colSpan.">";
		$html .="<span>".$allSets[$i]["set"]."</span>";
		$html .="<span> - </span>";
		$html .="<span>".$allSets[$i]["code"]."</span>";
		$html .="</th></tr>";
		$html .="<tr class='sort'>";
		$html .="<th colSpan=1 style='width: 250px'>Name</th>";
		$html .="<th colSpan=1 style='width: 100px'>Chart</th>";
		$html .="<th style='width: 70px'>PCT</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		
		if ($stackDisplay){
			$html .="<th style='width: 70px'>Stack</th>";			
		}
		$html .="<th style='width: 50px'></th>";
		$html .="<th style='width: 50px'></th>";
		$html .="<th style='width: 60px'>Rarity</th>";

		$html .="<th style='width: 80px'>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 80px'>".$allSets[$i]["lastDate"]."</th>";
	
		/*
		$html .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["lastDate"]."</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		$html .="<th style='width: 70px'>PCT</th>";
		$html .="</tr></thead>";
		*/
		
		
		$html .="<tbody>";

		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			$card = $allSets[$i]["shakers"][$j];
			
			//var_export($card); die();
			if ($card[$volChange][0] > 0){$class = "green";} else $class ="red";
			

			if ($skipUnchanged && $card[$volChange][$index] == 0){continue;}
			if ($minPrice != 0 && $card[$price][0] <= $minPrice){continue;}
			
			if ($plusminus){
				if (abs($card[$volChange][$index]) < abs($availChange)){continue;}
			}
			else {
				if ($availChange < 0 && $card[$volChange][$index] > $availChange){continue;}
				if ($availChange > 0 && $card[$volChange][$index] < $availChange){continue;}
			}		
			//die();		
			$cardUrl = "https://deckbox.org/mtg/".rawurlencode($card["name"]);

			$html .="<tr>";
			$html .="<td>";
			$html .= "<a target='_blank' href=".$cardUrl.">".$card['name']."</a>";
			$html .="</td>";
			$html .="<td class='smallChart'>";
			$html .="<td class='".$class."'>".$card[$volChange][1]." %</td>";
			$html .="<td class='".$class."'>".$card[$volChange][0]."</td>";
			
			/*
			$html .="<td>".$card[$price][sizeof($card[$price])-1]."</td>";
			$html .="<td>".$card[$price][0]."</td>";
			if ($card[$moneyChange][0] > 0){$class = "green";} else $class ="red";
			$html .="<td class='".$class."'>".$card[$moneyChange][0]."</td>";
			$html .="<td class='".$class."'>".$card[$moneyChange][1]." %</td>";
			$html .="</tr>";
			*/
			
			if ($stackDisplay){
	
				$string = "";
				$plus = "plus";
				$minus = "minus";
				
				//var_export($card["baseAvail"]);
				
				if ($foil){
					for ($k = 0; $k < sizeof($card["foilAvail"])-1; $k++){
						$val = round(($card["foilAvail"][$k] - $card["foilAvail"][$k+1]) / $card["foilAvail"][$k+1]*100, 2);
						if ($val > 0){
							$string .= "<span class='".$plus."'>".$val." %</span></br>";
						} else $string .= "<span class='".$minus."'>".$val." %</span></br>";
					}
				}
				else {
					for ($k = 0; $k < sizeof($card["foilAvail"])-1; $k++){
						$val = round(($card["baseAvail"][$k+1] - $card["baseAvail"][$k]) / $card["baseAvail"][$k]*100, 2)*-1;
						
						if ($val > 0){
							$string .= "<span class='".$plus."'>".$val." %</span></br>";
						} else $string .= "<span class='".$minus."'>".$val." %</span></br>";
					}
				}
				
				$html .="<td>".$string."</td>";
			}	
			$html .="<td>";
			
			$chartUrl = "charts.php?type=preset&set=".urlencode($allSets[$i]["set"])."&card=".urlencode($card["name"]);
			$html .= "<a target='_blank' href=".$chartUrl.">"."Chart"."</a>";
			$html .="</td>";
			$html .="<td>";
			
			$html .="<a target='_blank' href=".getMKMURL($allSets[$i]["set"], $card["name"]).">MKM</a></td>";
			$html .="</td>";
			$html .="<td>".substr($card["rarity"], 0, 1)."</td>";

			$html .="<td>".$card[$avail][sizeof($card[$avail])-1]."</td>";
			$html .="<td>".$card[$avail][0]."</td>";		
		}

		$html .="</tbody></table>";

	}
	return $html;
}

function writeAndCloseO($code, $data){
	echo "Writing ".$code.", entries: ".sizeof($data["data"])."\n";
	$GLOBALS["cards"] += sizeof($data["data"]);
	$file = fopen(__DIR__."/output/" . $code .".json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($data)."\n"."]}");
	fclose($file);
}

function writeAndClose($code, $data){
	echo "Writing ".$code.", entries: ".sizeof($data["data"])."\n";
	$GLOBALS["cards"] += sizeof($data["data"]);
	//$file = fopen(__DIR__."/output/" . $code .".json", "a");
	$file = fopen(__DIR__."/output/" . $code .".json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($data)."]}");
	fclose($file);
}


function fixOutputSets(){
	$sets = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["names"];

	//for ($i = 0; $i < 1; $i++){
	//	for ($j = 0; $j < 1; $j++){
	for ($i = 0; $i < sizeof($codes); $i++){
		for ($j = 0; $j < sizeof($codes[$i]); $j++){

			echo "doing set: ".$names[$i][$j]."</br>";
			$errorA = 0;
			$errorB = 0;
			$errorC = 0;
			$errorD = 0;

			$file = fopen(__DIR__."/output/" . $codes[$i][$j] .".json", "r+");
			fseek($file, 0);

			fwrite($file, '{"code": "'.$codes[$i][$j].'",');
			fwrite($file, "\n");
			fwrite($file, '"content": [');
			fwrite($file, "\n");

			//fclose($file);
			//$file = fopen(__DIR__."/output/" . $codes[$i][$j] .".json", "a");

			$data = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE);

			for ($k = 0; $k < sizeof($data["content"]); $k++){
				for ($l = 0; $l < sizeof($data["content"][$k]["data"]); $l++){

					$data["content"][$k]["code"] = $codes[$i][$j];

					if (!isset($data["content"][$k]["data"][$l]["baseAvail"])){$data["content"][$k]["data"][$l]["baseAvail"] = 0;$errorA++;}
					if (!isset($data["content"][$k]["data"][$l]["basePrice"])){$data["content"][$k]["data"][$l]["basePrice"] = 0;$errorB++;}
					if (!isset($data["content"][$k]["data"][$l]["foilAvail"])){$data["content"][$k]["data"][$l]["foilAvail"] = 0;$errorC++;}
					if (!isset($data["content"][$k]["data"][$l]["foilPrice"])){$data["content"][$k]["data"][$l]["foilPrice"] = 0;$errorD++;}
				}

				fwrite($file, json_encode($data["content"][$k]));
				fwrite($file, "\n");

				if ($k < sizeof($data["content"])-1){
					fwrite($file, ",");
				} else fwrite($file, "]}");
			}

			echo "found errors: ".$errorA."/".$errorB."/".$errorC."/".$errorD."</br>";
			fclose($file);
		}
	}
}


?>
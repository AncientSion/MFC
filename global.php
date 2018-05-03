<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__."/debug.php");
include(__DIR__."\simple_html_dom.php");



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
    echo (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i])."</br>";
}
	
function buildFullCardPool(){
	$sets = file_get_contents(__DIR__."/input/avail.json");
	$sets = json_decode($sets);

	$data = array();

	for ($i = 0; $i < sizeof($sets->codes); $i++){
		for ($j = 0; $j < sizeof($sets->codes[$i]); $j++){
			echo "adding set: ".$sets->codes[$i][$j]."</br>";
			$json = json_decode(file_get_contents(__DIR__."/input/".$sets->codes[$i][$j].".json"));

			$set = array("code" => $json->code, "name" => $json->name, "cards" => array());
			foreach ($json->cards as $card){
				$set["cards"][] = array("name" => $card->name, "rarity" => $card->rarity);
			}
			$data[] = $set;
		}
	}

	$file = fopen(__DIR__."/output/cardlist.json", "a");
	echo "writing</br>";
	fwrite($file, json_encode($data));
	fclose($file);
}



function getSetNamesByCodes($data){
	$sets = json_decode(file_get_contents(__DIR__."/input/avail.json"), TRUE);

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
	$html .="<div id='depth'>Depths</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='1' max='50' value='".$depth."' name='depth'>";
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
	$html .= "<input type='number' min='0' max='10000' value='".$maxAvail."' name='maxAvail'>";
	$html .= "</div>";
	$html .= "</div>";

	$minPrice = 0;
	if (sizeof($get)){$minPrice = $get["minPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='minPrice'>Min (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='0' max='5000' step='0.5' value='".$minPrice."' name='minPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxPrice = 0;
	if (sizeof($get)){$maxPrice = $get["maxPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='maxPrice'>Max (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number'min='0' max='5000' value='".$maxPrice."' name='maxPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$availChange = -1;
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



	$sets = json_decode(file_get_contents(__DIR__."/input/avail.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["codes"];

	//var_export($codes);
	//echo "</br></br>";
	//var_export($get["sets"]);

	for ($i = 0; $i < sizeof($codes); $i++){
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

			$html .="<div class='checkContainer'><input type='checkbox' name='sets[]' value='".$codes[$i][$j]."' ".$checked.">";
			$html .="<span>".$codes[$i][$j]."</span>";
			$html .="</div>";
		}
		//$html .="</br>";
	}


	/*
	$html .="<div class='checkContainer'><input type='checkbox' name='sets[]' value='BOXES'".$checked.">";
	$html .="<span>BOXES</span>";
	$html .="</div>";
	*/
	$html .="</div>";
	$html .="</br>";
	$html .="<input type='submit' style='width: 200px; font-size: 26px' value='Search'></input>";
	$html .="</form>";

	return $html;
}






function writeBoosterInput(){
	$set = array(
		"name" => "Booster Boxes",
		"cards" => array(),
	);


	$data =  file_get_contents(__DIR__."/output/BOXES.json");
	$data = json_decode($data);

	$entry = $data->content[0]->data;

	foreach ($entry as $box){
		//echo "day: ".$day->date.": ".sizeof($day->data)."\n";
		$set["cards"][] = array("name" => $box->name, "rarity" => "D");
	}

	$file = fopen(__DIR__."/input/BOXES.json", "a");
	echo "writing \n";
	fwrite($file, json_encode($set));
	fclose($file);
}



function requestShakers($codes, $includes, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChange, $compareType){
	//var_export(func_get_args());
	//echo $minAvail; echo $maxAvail;

	$sets = json_decode(file_get_contents(__DIR__."/input/avail.json"), TRUE);

	//logSearch($codes, $includes, $foil, $depth, $minPrice, $maxPrice, $availChange, $compareType);
	$names = getSetNamesByCodes($codes);
	$allSets = array();

	//$codes = array(array("A25"));

	for ($i = 0; $i < sizeof($codes); $i++){
		$setName = $names[$i];

		$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i].".json"), TRUE);
		$cards = $cards["cards"];
		$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i].".json"), TRUE);
		$points = $points["content"];
		if (!$points){continue;}
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
			if ($minAvail && ((!$foil && $last["baseAvail"] < $minAvail) || ($foil && $last["foilAvail"] < $minAvail))){continue;}
			if ($maxAvail && ((!$foil && $last["baseAvail"] > $maxAvail) || ($foil && $last["foilAvail"] > $maxAvail))){continue;}

			if ($minPrice && $last["foilPrice"] < $minPrice){continue;}
			if ($maxPrice && $last["foilPrice"] > $maxPrice){continue;}
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
	
	//echo $html;
	echo buildTables($allSets, $foil, $compareType, $availChange, $minPrice);

}


function setChangeValue(&$card, $attr){
	if ($card[$attr][sizeof($card[$attr])-1] == 0){$card[$attr."Change"][0] = 0; $card[$attr."Change"][1] = 0; return;}
	//var_export($card);
	$card[$attr."Change"][0] = round($card[$attr][0] - $card[$attr][sizeof($card[$attr])-1], 2); 
	$card[$attr."Change"][1] = round((($card[$attr][0] / $card[$attr][sizeof($card[$attr])-1])*100)-100, 2); 
}


function buildTables($allSets, $foil, $compareType, $availChange, $minPrice){
	//var_export($allSets);

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
	$html = "<span style='color: red'>NOTE: Prefer the # (available stock) columns, price is biased because of 'cheap' foreign foils</span>";
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

	if ($compareType == "abs"){
		$index = 0;
	} else $index = 1;

	$mkmBaseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

	for ($i = 0; $i < sizeof($allSets); $i++){
		$html .="<table class='moveTable'>";

		$html .="<thead>";
		$html .="<tr><th class='set' colSpan=10>".$allSets[$i]["set"]." - ".$allSets[$i]["code"]."</th></tr>";
		$html .="<tr class='sort'>";
		$html .="<th style='width: 200px'>Name</th>";
		$html .="<th style='width: 60px'>Rarity</th>";

		$html .="<th style='width: 100px'>Stock</br>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 100px'>Stock</br>".$allSets[$i]["lastDate"]."</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		$html .="<th style='width: 70px'>PCT</th>";

		$html .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["lastDate"]."</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		$html .="<th style='width: 70px'>PCT</th>";
		$html .="</tr></thead>";

		$html .="<tbody>";

		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			$card = $allSets[$i]["shakers"][$j];

			if ($minPrice != 0 && $card[$price][0] <= $minPrice){continue;}
			if ($availChange != 0 && $card[$volChange][$index] > $availChange){continue;}

			$chartUrl = "charts.php?type=preset&set=".urlencode($allSets[$i]["set"])."&card=".urlencode($card["name"]);
			$mkmUrl = $mkmBaseUrl.urlencode($allSets[$i]["set"]) . "/" . urlencode($card["name"]);

			$html .="<tr><td><a target='_blank' href=".$chartUrl.">".$card['name']."</a>";
			$html .=" - ";
			$html .="<a target='_blank' href=".$mkmUrl.">MKM</a></td>";
			$html .="<td>".substr($card["rarity"], 0, 1)."</td>";

			$html .="<td>".$card[$avail][sizeof($card[$avail])-1]."</td>";
			$html .="<td>".$card[$avail][0]."</td>";
			if ($card[$volChange][0] > 0){$class = "green";} else $class ="red";
			$html .="<td class='".$class."'>".$card[$volChange][0]."</td>";
			$html .="<td class='".$class."'>".$card[$volChange][1]." %</td>";

			$html .="<td>".$card[$price][sizeof($card[$price])-1]."</td>";
			$html .="<td>".$card[$price][0]."</td>";
			if ($card[$moneyChange][0] > 0){$class = "green";} else $class ="red";
			$html .="<td class='".$class."'>".$card[$moneyChange][0]."</td>";
			$html .="<td class='".$class."'>".$card[$moneyChange][1]." %</td>";
			$html .="</tr>";
		}

		$html .="</tbody></table>";

	}
	return $html;
}

function writeAndClose($code, $data){
	echo "Writing ".$code.", entries: ".sizeof($data["data"])."\n";
	$GLOBALS["cards"] += sizeof($data["data"]);
	//$file = fopen(__DIR__."/output/" . $code .".json", "a");
	$file = fopen(__DIR__."/output/" . $code .".json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($data)."\n"."]}");
	fclose($file);
}


function fixOutputSets(){
	$sets = json_decode(file_get_contents(__DIR__."/input/avail.json"), TRUE);
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
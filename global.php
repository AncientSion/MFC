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

	$file = fopen(__DIR__."/output/cardlist.json", "a");
	fwrite($file, json_encode($data));
	fclose($file);
}


function requestShakers($codes, $skips, $depth, $foil, $minPrice, $maxPrice, $availChange, $compareType){

	$codes = $sets["codes"];
	$names = $sets["names"];

	$depth = 10;
	$foil = 1;
	$minPrice = 0.5;
	$maxPrice = 0;
	$availChange = -0.0;
	$skips = array("C", "R", "M", "S");
	$compareType = "pct";

	echo "Checking card prices</br>";
	echo "Delving: ".$depth." days of data.</br>";
	echo "Foil: ".$foil."</br>";
	echo "Price NOW > ".$minPrice."</br>";
	echo "Price NOW < ".$maxPrice."</br>";
	echo "Supply Avail Change > ".$availChange." ".$compareType."</br>";

	$allSets = array();

	//$codes = array(array("A25"));

	for ($i = 0; $i < sizeof($codes); $i++){
		for ($j = 0; $j < sizeof($codes[$i]); $j++){
	//for ($i = 0; $i < 1; $i++){
	//	for ($j = 0; $j < 1; $j++){
			$setName = $names[$i][$j];


			//echo "Preparing ".$setName." prices</br>";


			$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i][$j].".json"), TRUE)["cards"];
			$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE)["content"];
			if (!$points){echo "</br></br>ERRROR ".$setName; continue;}
			$extract = array(
				"set" => $setName,
				"compareDate" => $points[max(0, (sizeof($points)-1 -$depth))]["date"],
				"lastDate" => $points[sizeof($points)-1]["date"],
				"shakers" => array()
			);

			for ($k = 0; $k < sizeof($cards); $k++){
			//for ($k = 0; $k < 30; $k++){

				$skip = false;
				for ($l = 0; $l < sizeof($skips); $l++){
					if ($cards[$k]["rarity"][0] == $skips[$l]){$skip = true;}
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
	}


	for ($i = 0; $i < sizeof($allSets); $i++){
		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			setChangeValue($allSets[$i]["shakers"][$j], "baseAvail");
			setChangeValue($allSets[$i]["shakers"][$j], "basePrice");
			setChangeValue($allSets[$i]["shakers"][$j], "foilAvail");
			setChangeValue($allSets[$i]["shakers"][$j], "foilPrice");
		}
	}

	$time += microtime(true);
	echo "</br>Markup Completed; TIME:".round($time, 2)." seconds.</br></br>";


	$html = buildTables($allSets, $foil, $compareType, $availChange, $minPrice);

}


function setChangeValue(&$card, $attr){
	if ($card[$attr][sizeof($card[$attr])-1] == 0){$card[$attr."Change"][0] = 0; $card[$attr."Change"][1] = 0; return;}
	//var_export($card);
	$card[$attr."Change"][0] = round($card[$attr][0] - $card[$attr][sizeof($card[$attr])-1], 2); 
	$card[$attr."Change"][1] = round((($card[$attr][0] / $card[$attr][sizeof($card[$attr])-1])*100)-100, 2); 
}


function buildTables($allSets, $foil, $compareType, $availChange, $minPrice){
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
	$index;

	if (!$foil){
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


	for ($i = 0; $i < sizeof($allSets); $i++){
		//$html .="entries: ".sizeof($allSets[$i]["shakers"])."</br>";
		$html .="<table class='moveTable'>";


		$html .="<thead>";
		$html .="<tr><th class='set' colSpan=10>".$allSets[$i]["set"]."</th></tr>";
		$html .="<tr class='sort'>";
		$html .="<th style='width: 180px'>Name</th>";
		$html .="<th style='width: 100px'>Rarity</th>";

		$html .="<th style='width: 100px'>#</br>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 100px'>#</br>".$allSets[$i]["lastDate"]."</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		$html .="<th style='width: 70px'>PCT</th>";

		$html .="<th style='width: 100px'>€</br>".$allSets[$i]["compareDate"]."</th>";
		$html .="<th style='width: 100px'>€</br>".$allSets[$i]["lastDate"]."</th>";
		$html .="<th style='width: 70px'>ABS</th>";
		$html .="<th style='width: 70px'>PCT</th>";
		$html .="</tr></thead>";

		$html .="<tbody>";

		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			$card = $allSets[$i]["shakers"][$j];

			if ($minPrice != 0 && $card[$price][0] <= $minPrice){continue;}
			if ($availChange != 0 && $card[$volChange][$index] > $availChange){continue;}
			//var_export($allSets[$i]["shakers"][$j]);
			//echo "</br></br>";

			//echo $card["name"].", price: ".$card[$price][sizeof($card[$price])-1];
			$html .="<tr><td>".$card["name"]."</td>";
			$html .="<td>".$card["rarity"]."</td>";

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

function fixOutputSets(){
	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
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
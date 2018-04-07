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



?>
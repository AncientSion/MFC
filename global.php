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

function fixOutputSets(){
	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["names"];

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
				//echo "size: ".sizeof($data["content"][$k]["data"])."</br>";
				for ($l = 0; $l < sizeof($data["content"][$k]["data"]); $l++){
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
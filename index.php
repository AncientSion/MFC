<?php
	
	if (isset($_GET["type"])){
		if ($_GET["type"] == "cardList"){
			$cards = file_get_contents(__DIR__."/output/cardlist.json");
			echo $cards;	
		} else if ($_GET["type"] == "price"){
			$set = $_GET["set"];
			$card = $_GET["card"];

			$dataPoints = array();
			$file = file_get_contents(__DIR__."/output/".$set.".json");
			$json = json_decode($file);
			if ($json == NULL){
				echo json_encode(array("msg" => "no card price data found"));
				return;
			}
			//return;
			//var_export($json->content);
			for ($i = 0; $i < sizeof($json->content); $i++){
				for ($j = 0; $j < sizeof($json->content[$i]->data); $j++){
					if ($json->content[$i]->data[$j]->name == $card){
						$dataPoints[] = array("time" => $json->content[$i]->date, "data" => $json->content[$i]->data[$j]);
					}
				}
			}
			echo json_encode($dataPoints);
		}
		return;
	}



	if (1){
		$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
		$codes = $sets["codes"];
		$names = $sets["names"];


		//for ($i = 0; $i < sizeof($codes); $i++){
		//	for ($j = 0; $j < sizeof($codes[$i]); $j++){
		for ($i = 0; $i < 1; $i++){
			for ($j = 0; $j < 1; $j++){
				$data = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE);

				for ($k = 0; $k < sizeof($data["content"]); $k++){
					for ($l = 0; $l < sizeof($data["content"][$k]["data"]); $l++){
	if (!isset($data["content"][$k]["data"][$l]["baseAvail"])){echo "no baseAvail</br>"; $data["content"][$k]["data"][$l]["baseAvail"] = 0;}
	if (!isset($data["content"][$k]["data"][$l]["basePrice"])){echo "no basePrice</br>"; $data["content"][$k]["data"][$l]["basePrice"] = 0;}
	if (!isset($data["content"][$k]["data"][$l]["foilAvail"])){echo "no foilAvail</br>"; $data["content"][$k]["data"][$l]["foilAvail"] = 0;}
	if (!isset($data["content"][$k]["data"][$l]["foilPrice"])){echo "no foilPrice</br>"; $data["content"][$k]["data"][$l]["foilPrice"] = 0;}
					}
				}

				$file = fopen(__DIR__."/output/" . $codes[$i][$j] .".json", "r+");
				fseek($file, 0);


				fwrite($file, '{"code": "A25",');				
				fwrite($file, "\n");
				fwrite($file, '"content": [');
				//fwrite($file, json_encode($data));
				echo "WRTIE!";
				fclose($file);
			}
		}

		return;
	}

	

	if (0){
		$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
		$codes = $sets["codes"];
		$names = $sets["names"];

		$depth = 5;

		//for ($i = 0; $i < sizeof($codes); $i++){
			//for ($j = 0; $j < sizeof($codes[$i]); $j++){
		for ($i = 0; $i < 1; $i++){
			for ($j = 0; $j < 1; $j++){
				$setName = $names[$i][$j];

				$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i][$j].".json"), TRUE)["cards"];
				$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE)["content"];	


				//for ($k = 0; $k < sizeof($cards); $k++){
				for ($k = 0; $k < 3; $k++){
					$name = $cards[$k]["name"];
					$last = getCardDataSet($name, $points[sizeof($points)-1]["data"]);

					$cardData = array(
						"name" => $name,
						"baseAvail" => array($last["baseAvail"]),
						"basePrice" => array($last["basePrice"]),
						"foilAvail" => array($last["foilAvail"]),
						"foilPrice" => array($last["foilPrice"]),
					);

					if (!$last){continue;}

					for ($l = sizeof($points)-2; $l >= max(0, sizeof($points)-2 - $depth); $l--){
						addCardDataSet($cardData, getCardDataSet($name, $points[$l]["data"]));
					}

					var_export($cardData);
				}
			}
		}
	}

	function getCardDataSet($name, $data){
		for ($i = 0; $i < sizeof($data); $i++){
			if ($data[$i]["name"] == $name){
				return $data[$i];
			}
		}
		return false;
	}

	function addCardDataSet($data, $set){
		$cardData["baseAvail"][] = $set["baseAvail"];
		$cardData["basePrice"][] = $set["basePrice"];
		$cardData["foilAvail"][] = $set["foilAvail"];
		$cardData["foilPrice"][] = $set["foilPrice"];
	}


	function getMemory(){
		$size = memory_get_usage(true);
	    $unit=array('b','kb','mb','gb','tb','pb');
	    echo (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i])."</br>";
	}
?>


<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' href='libs\style.css'/>
	<link rel='stylesheet' href='libs\jquery-ui.min.css'/>
	<script src="libs\jquery-2.1.1.min.js"></script>
	<script src='libs\jquery-ui.min.js'></script>
	<script src='libs\Chart.bundle.min.js'></script>	
	<script src='script.js'></script>
</head>
	<body>
		<div class="mainContainer">
			<div class="container">
				<canvas id="foilAvailCanvas"</canvas>
			</div>
			<div class="container">
				<canvas id="foilPriceCanvas"</canvas>
			</div>
			<div class="ui">
				<div>
					<input type="form" id="setSearch" value="Masters 25">
					<input type="form" id="cardSearch" value="Rishadan Port">
					<input type="button" style="font-size: 20px" onclick="charter.getCardData(this)" value="Search">
					<div id="cardName"></div>
				</div>
			</div>
			<div class="container" style="margin-top: 50px">
				<canvas id="baseAvailCanvas"</canvas>
			</div>
			<div class="container">
				<canvas id="basePriceCanvas"</canvas>
			</div>
		</div>	
	</body>
</html>

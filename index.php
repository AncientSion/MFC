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



	if (0){ // fix missing data
		$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
		$codes = $sets["codes"];
		$names = $sets["names"];


		for ($i = 0; $i < sizeof($codes); $i++){
			for ($j = 0; $j < sizeof($codes[$i]); $j++){
		//for ($i = 0; $i < 1; $i++){
			//for ($j = 0; $j < 1; $j++){

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

		return;
	}

	

	if (0){
		$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
		$codes = $sets["codes"];
		$names = $sets["names"];

		$depth = 3;
		$foilPriceMin = 20;
		echo "Checking MASTERS 25 card prices</br>";
		echo "Delving: ".$depth." days of data.</br>";
		echo "Searching: Rare</br>";
		echo "Foil Price NOW > ".$foilPriceMin."</br>";
		echo "</br></br>";

		//for ($i = 0; $i < sizeof($codes); $i++){
			//for ($j = 0; $j < sizeof($codes[$i]); $j++){
		for ($i = 0; $i < 1; $i++){
			for ($j = 0; $j < 1; $j++){
				$setName = $names[$i][$j];

				$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i][$j].".json"), TRUE)["cards"];
				$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE)["content"];	


				for ($k = 0; $k < sizeof($cards); $k++){
					if ($cards[$k]["rarity"][0] == "C"){continue;}
					if ($cards[$k]["rarity"][0] == "U"){continue;}
					$name = $cards[$k]["name"];
					$last = getCardDataSet($name, $points[sizeof($points)-1]["data"]);
					if (!$last){continue;}
					if ($last["foilPrice"] < $foilPriceMin){continue;}
					$card = array(
						//"name" => $name,
						"baseAvail" => array(),
						"basePrice" => array(),
						"foilAvail" => array(),
						"foilPrice" => array()
					);


					for ($l = sizeof($points)-1; $l >= max(0, (sizeof($points)-1 -$depth)); $l--){
						addCardDataPoint($card, getCardDataSet($name, $points[$l]["data"]));
					}
					//echo "</br>"; var_export($card); echo "</br>";

					echo $names[$i][$j]." - ".$name."</br>";
					foreach ($card as $key => $value){
						$shift = round((($card[$key][0] / $card[$key][sizeof($card[$key])-1])*100)-100, 2);
						$color = "green";
						$type = "increase +";
						if ($shift < 0){
							$color = "red";
							$type = "decrease";
						}
						echo $key." --- then: ".$card[$key][sizeof($card[$key])-1].", now ".$card[$key][0]." => <span class='".$color."'> ".$type." ".$shift."%</span>.";
						echo "</br>";
					}
					echo "</br>";
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

	function addCardDataPoint(&$currentSet, $point){
		//echo "</br>adding point</br>";
		//var_export($point);
		$currentSet["baseAvail"][] = $point["baseAvail"];
		$currentSet["basePrice"][] = $point["basePrice"];
		$currentSet["foilAvail"][] = $point["foilAvail"];
		$currentSet["foilPrice"][] = $point["foilPrice"];

		//echo "</br>current set</br>";
		//var_Export($currentSet);
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
			<div class="ui disabled">
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

<?php

	include_once(__DIR__."/global.php");

	$time = time();
	$today = date('d.m.Y', $time);
	$compareDate;
	$time = -microtime(true);


	$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
	$codes = $sets["codes"];
	$names = $sets["names"];

	$depth = 10;
	$foilPriceMin = 1;
	$foilAvailShift = -5;
	//$skips = array("C", "U", "R", "M", "S");
	$skips = array();
	$search = "foilAvail";



	echo "Checking card prices</br>";
	echo "Delving: ".$depth." days of data.</br>";
	echo "Foil Price NOW > ".$foilPriceMin."</br>";
	echo "Foil Avail Shift > ".$foilAvailShift."</br>";

	$allSets = array();

	for ($i = 0; $i < sizeof($codes); $i++){
		for ($j = 0; $j < sizeof($codes[$i]); $j++){
	//for ($i = 0; $i < 1; $i++){
	//	for ($j = 0; $j < 1; $j++){
			$setName = $names[$i][$j];


			//echo "Preparing ".$setName." prices</br>";


			$cards = json_decode(file_get_contents(__DIR__."/input/".$codes[$i][$j].".json"), TRUE)["cards"];
			$points = json_decode(file_get_contents(__DIR__."/output/".$codes[$i][$j].".json"), TRUE)["content"];

			$extract = array(
				"set" => $setName,
				"compareDate" => $points[max(0, (sizeof($points)-1 -$depth))]["date"],
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
				if ($foilPriceMin != 0 && $last["foilPrice"] < $foilPriceMin){continue;}
				$card = array(
					"name" => $name,
					"rarity" => $cards[$k]["rarity"],
					"baseAvail" => array(),
					"basePrice" => array(),
					"foilAvail" => array(),
					"foilPrice" => array(),
					"baseAvailShift" => array(),
					"basePriceShift" => array(),
					"foilAvailShift" => array(),
					"foilPriceShift" => array()
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
			setShiftValue($allSets[$i]["shakers"][$j], "baseAvail");
			setShiftValue($allSets[$i]["shakers"][$j], "basePrice");
			setShiftValue($allSets[$i]["shakers"][$j], "foilAvail");
			setShiftValue($allSets[$i]["shakers"][$j], "foilPrice");
		}
	}

	$time += microtime(true);
	echo "</br>Markup Completed; TIME:".round($time, 2)." seconds.</br></br>";


	$html = buildTables($allSets, $today, $foilAvailShift);



	function setShiftValue(&$card, $attr){
		if ($card[$attr][sizeof($card[$attr])-1] == 0){$card[$attr."Shift"][0] = 0; $card[$attr."Shift"][1] = 0; return;}
		//var_export($card);
		$card[$attr."Shift"][0] = round($card[$attr][0] - $card[$attr][sizeof($card[$attr])-1], 2); 
		$card[$attr."Shift"][1] = round((($card[$attr][0] / $card[$attr][sizeof($card[$attr])-1])*100)-100, 2); 
	}


	function buildTables($allSets, $today, $foilAvailShift){
		$html = "";
		for ($i = 0; $i < sizeof($allSets); $i++){
			//$html .="entries: ".sizeof($allSets[$i]["shakers"])."</br>";
			$html .="<table class='moveTable'><tbody>";
			$html .="<tr><th class='set' colSpan=10>".$allSets[$i]["set"]."</th></tr>";
			$html .="<tr>";
			$html .="<th style='width: 180px'>Name</th>";
			$html .="<th style='width: 100px'>Rarity</th>";

			$html .="<th style='width: 100px'>Foil # ".$allSets[$i]["compareDate"]."</th>";
			$html .="<th style='width: 100px'>Foil # ".$today."</th>";
			$html .="<th style='width: 100px'>ABS</th>";
			$html .="<th style='width: 100px'>PCT</th>";

			$html .="<th style='width: 100px'>Foil € ".$allSets[$i]["compareDate"]."</th>";
			$html .="<th style='width: 100px'>Foil € ".$today."</th>";
			$html .="<th style='width: 100px'>ABS</th>";
			$html .="<th style='width: 100px'>PCT</th>";

			for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
				$card = $allSets[$i]["shakers"][$j];

				if ($foilAvailShift != 0 && $card["foilAvailShift"][0] > $foilAvailShift){continue;}

				$html .="<tr><td>".$card["name"]."</td>";
				$html .="<td>".$card["rarity"]."</td>";

				$html .="<td>".$card["foilAvail"][sizeof($card["foilAvail"])-1]."</td>";
				$html .="<td>".$card["foilAvail"][0]."</td>";
				if ($card["foilAvailShift"][0] > 0){$class = "green";} else $class ="red";
				$html .="<td class='".$class."'>".$card["foilAvailShift"][0]."</td>";
				$html .="<td class='".$class."'>".$card["foilAvailShift"][1]."</td>";

				$html .="<td>".$card["foilPrice"][sizeof($card["foilPrice"])-1]."</td>";
				$html .="<td>".$card["foilPrice"][0]."</td>";
				if ($card["foilPriceShift"][0] > 0){$class = "green";} else $class ="red";
				$html .="<td class='".$class."'>".$card["foilPriceShift"][0]."</td>";
				$html .="<td class='".$class."'>".$card["foilPriceShift"][1]."</td>";
				$html .="</tr>";
			}

			$html .="</table></tbody>";

		}
		return $html;
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
</head>
	<body>



	<?php

		echo $html;
		/*
		$rarity = array("Common", "Uncommon", "Rare", "Mythic Rare", "Special");
		for ($i = 0; $i < sizeof($rarity); $i++){
			echo "<div class='checkContainer'><input type='checkbox' name='PLJan' checked='checked'>".$rarity[$i]."</input></div>"; 
		}

		echo "<div class='checkContainer' style='margin-left: 100px'><input type='checkbox' name='PLJan' checked='checked'>Only Foil</input></div>";

		for ($i = 0; $i < sizeof($codes); $i++){
			echo "</br>";
			for ($j = 0; $j < sizeof($codes[$i]); $j++){
				echo "<div class='checkContainer'><input type='checkbox' name='' checked='checked'>".$names[$i][$j]."</input></div>"; 
			}
		}
		*/
	?>
	</body>
</html>

<script>
$(document).ready(function(){
	$(".moveTable").each(function(){
		if (this.childNodes[0].childNodes.length < 3){
			$(this).remove();
		}
	})
})
</script>
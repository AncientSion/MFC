<?php
	include_once(__DIR__."/global.php");


	if (isset($_GET["type"])){
		if ($_GET["type"] == "cardlist"){
			$cards = file_get_contents(__DIR__."/output/cardlist.json");
			echo $cards;	
			return;
		}
		else if ($_GET["type"] == "price"){
			//var_export($_GET); return;
			$set = $_GET["set"];
			$card = $_GET["card"];

			logChart($set, $card);
			$dataPoints = array();
			$file = file_get_contents(__DIR__."/output/".$set.".json");
			$json = json_decode($file);
			if ($json == NULL){
				echo json_encode(array("msg" => "no card price data found"));
				return;
			}
			
			$days = sizeof($json->content);
		/*	$keep = 1;
			if ($days > 200){
				$keep = 3;
			}
			else if ($days > 100){
				$keep = 2;
			}
		*/	
			for ($i = 0; $i < sizeof($json->content); $i++){
				//if ($i % $keep != 0){continue;}
				for ($j = 0; $j < sizeof($json->content[$i]->data); $j++){
					if ($json->content[$i]->data[$j]->name == $card){
						$dataPoints[] = array("time" => $json->content[$i]->date, "data" => $json->content[$i]->data[$j]);
					}
				}
			}
			
			echo json_encode($dataPoints);
			return;
		}
	}
?>


<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' href='style\style.css'/>
	<link rel='stylesheet' href='style\jquery-ui.min.css'/>
	<script src="libs\jquery-2.1.1.min.js"></script>
	<script src='libs\jquery-ui.min.js'></script>
	<script src='libs\Chart.bundle.min.js'></script>	
	<script src='script.js'></script>
</head>
	<body>


		<?php

			echo "<div class='mainContainer'>
				<div class='container'>
					<canvas id='foilAvailCanvas'</canvas>
				</div>
				<div class='container'>
					<canvas id='foilPriceCanvas'</canvas>
				</div>
				<div class='search disabled'>";

				$card = "";
				$set = "";

				if (sizeof($_GET) && ($_GET["type"] == "preset")){
					$set = $_GET["set"];
					$card = $_GET["card"];
					echo "<script>window.remote = 1</script>";
				} else echo "<script>window.remote = 0</script>";

			echo '<input type="form" class="setSearch" value="'.$set.'">';
				echo '<input type="form" class="cardSearch" value="'.$card.'">';
		?>

		<input type="button" style="font-size: 20px" onclick="charter.getCardData(0, $('.setSearch').val(), $('.cardSearch').val())" value="Search">
		<div id="cardName"></div>
		<div class="reprints"></div>

		<?php
			echo "</div>";
			echo "<div class='container'>
					<canvas id='baseAvailCanvas'</canvas>s
				</div>
				<div class='container'>
					<canvas id='basePriceCanvas'</canvas>
				</div>";

		?>

	</body>
</html>

<script type="text/javascript">

	const charter = new Charter();
	
	window.onload = function(){
		timeout = false;

		//$(".ui").removeClass("disabled");

		if (remote){
			timeout = setTimeout(function(){
				charter.getCardData(0, $(".setSearch").val(), $(".cardSearch").val())
			}, 300);
		}
}

</script>

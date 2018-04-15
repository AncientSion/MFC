<?php
	include_once(__DIR__."/global.php");

	//buildFullCardPool(); return;

	if (isset($_GET["type"])){
		if ($_GET["type"] == "cardList"){
			$cards = file_get_contents(__DIR__."/output/cardlist.json");
			echo $cards;	
			return;
		}
		else if ($_GET["type"] == "price"){
			$set = $_GET["set"];
			$card = $_GET["card"];

			//echo $set; echo $card;

			$dataPoints = array();
			$file = file_get_contents(__DIR__."/output/".$set.".json");
			$json = json_decode($file);
			if ($json == NULL){
				echo json_encode(array("msg" => "no card price data found"));
				return;
			}
			for ($i = 0; $i < sizeof($json->content); $i++){
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
		<div class="mainContainer">
			<div class="container">
				<canvas id="foilAvailCanvas"</canvas>
			</div>
			<div class="container">
				<canvas id="foilPriceCanvas"</canvas>
			</div>
			<div class="ui disabled">
				<div>
					<?php 

					$card = "Brainstorm";
					$set = "Masters 25";

						if (sizeof($_GET) && ($_GET["type"] == "preset")){
							$card = $_GET["card"];
							$set = $_GET["set"];
							echo "<script>window.remote = 1</script>";
						} else "<script>window.remote = 0</script>";

						echo "<input type='form' id='setSearch' value='".$set."'>";
						echo "<input type='form' id='cardSearch' value='".$card."'>";

					?>
					
					<input type="button" style="font-size: 20px" onclick="charter.getCardData()" value="Search">
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
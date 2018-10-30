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

			//echo $card;

			logChart($set, $card);
			$dataPoints = array();
			$file = file_get_contents(__DIR__."/output/".$set.".json");
			$json = json_decode($file);
			if ($json == NULL){
				echo json_encode(array("msg" => "no card price data found"));
				return;
			}
			
			$days = sizeof($json->content);
			$keep = 1;
			if ($days > 200){
				$keep = 3;
			}
			else if ($days > 100){
				$keep = 2;
			}
			
			for ($i = 0; $i < sizeof($json->content); $i++){
				if ($i % $keep != 0){continue;}
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
							$set = $_GET["set"];
							$card = $_GET["card"];
							echo "<script>window.remote = 1</script>";
						} else echo "<script>window.remote = 0</script>";

						echo '<input type="form" id="setSearch" value="'.$set.'">';
						echo '<input type="form" id="cardSearch" value="'.$card.'">';


					?>
					
					<input type="button" style="font-size: 20px" onclick="charter.getCardData($('#setSearch').val(), $('#cardSearch').val())" value="Search">
					<div id="cardName"></div>
				</div>
			</div>
			<div class="container">
				<canvas id="baseAvailCanvas"</canvas>
			</div>
			<div class="container">
				<canvas id="basePriceCanvas"</canvas>
			</div>
		</div>	
	</body>
</html>

<script type="text/javascript">
	
	window.onload = function(){
		charter = new Charter();
		timeout = false;

		$("#cardSearch").focus(function(){
			//console.log("ding");
			charter.isValidSetSelected();
		})

		$(".ui").removeClass("disabled");

		if (remote){
			timeout = setTimeout(function(){
				charter.getCardData($("#setSearch").val(), $("#cardSearch").val())
			}, 300);
		}
}

</script>

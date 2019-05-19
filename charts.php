<?php
	include_once(__DIR__."/global.php");


	if (isset($_GET["type"])){
		if ($_GET["type"] == "cardlistNew"){
			$cards = DB::app()->getAllCards();
			echo json_encode($cards);
			//echo var_export($cards);
			return;
		}
		if ($_GET["type"] == "cardlist"){
			$cards = file_get_contents(__DIR__."/output/cardlist.json");
			echo $cards;	
			return;
		}
		else if ($_GET["type"] == "price"){
			$set = $_GET["set"];
			$card = $_GET["card"];
			$data = DB::app()->getChartData($set, $card);
			echo json_encode($data);
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
				<div class='search'>";

				$card = "Brainstorm";
				$set = "A25";

				if (sizeof($_GET) && ($_GET["type"] == "preset")){
					$set = $_GET["set"];
					$card = $_GET["card"];
					echo "<script>window.remote = 1</script>";
				} else echo "<script>window.remote = 0</script>";

			echo '<input type="form" class="setSearch" value="'.$set.'">';
				echo '<input type="form" class="cardSearch" value="'.$card.'">';
		?>

		<input type="button" style="font-size: 16px" onclick="charter.getCardData(0, $('.setSearch').val(), $('.cardSearch').val())" value="Search">

		<input type='checkbox'></input>
		<input type="button" style="font-size: px" onclick="charter.assembleFavData()" value="add">
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

<style>
	.mainContainer {
		width: 850px;
	}

	.mainContainer div {
		width: 400px;
	}

	.mainContainer div.search {
		width: 100%;
	}
</style>
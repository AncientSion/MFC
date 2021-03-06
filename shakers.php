<?php

	include_once(__DIR__."/global.php");
		
	//buildFullCardPool(); return;

	if (sizeof($_GET)){
		if (isset($_GET["type"]) && $_GET["type"] == "cardrules"){
			$cardname = $_GET["cardName"];
			$setname = $_GET["setCode"];
			if ($setname == ""){return;}
			echo requestCardText($cardname, $setname);
			return;
		}
		else if (isset($_GET["rarities"]) && isset($_GET["foil"]) && isset($_GET["depth"]) && isset($_GET["sets"])){

			echo getForm($_GET);

			$time = time();
			$time = -microtime(true);

			$depth = $_GET["depth"];
			$minAvail = $_GET["minAvail"];
			$maxAvail = $_GET["maxAvail"];
			$minPrice = $_GET["minPrice"];
			$maxPrice = $_GET["maxPrice"];
			$availChangeMin = $_GET["availChangeMin"];
			$availChangeMax = $_GET["availChangeMax"];
			$plusminus = 0; if (isset($_GET["plusminus"])){$plusminus = 1;}
			$stackDisplay = 0; if (isset($_GET["stackDisplay"])){$stackDisplay = 1;}
			$skipUnchanged = 0; if (isset($_GET["skipUnchanged"])){$skipUnchanged = 1;}
			$type = $_GET["compareType"];
			$foil = 0; if ($_GET["foil"] == "Is Foil"){$foil = 1;}

			echo "<div class='mainContainer'>
				<div class='contInnerWrapper'>
					<div class='container'>
						<canvas id='basePriceCanvas'</canvas>
					</div>
					<div class='container'>
						<canvas id='baseAvailCanvas'</canvas>
					</div>
					<div id='card'></div>
					<div class='container'>
						<canvas id='foilPriceCanvas'</canvas>
					</div>
					<div class='container'>
						<canvas id='foilAvailCanvas'</canvas>
					</div>
				</div>
			</div>";

			echo "<div class='scrollWrapper'>";
			echo requestAllShakers(
				$_GET["sets"],
				$_GET["rarities"],
				$foil,
				$depth,
				$minAvail,
				$maxAvail,
				$minPrice,
				$maxPrice,
				$availChangeMin,
				$availChangeMax,
				$plusminus,
				$stackDisplay,
				$skipUnchanged,
				$type
			);
			echo "</div>";
			echo "<script>const options = {autoHideUI: 1, setAll: 0, rarityAll: 0};</script>";
			$time += microtime(true);
			//echo "Script Execution Completed; TIME:".round($time, 2)." seconds.";
		}
		else {
			echo "<div style='color: red; font-size: 30px'>Brah, no sets -> no results ... !</div>";
			echo getForm(array());
			echo "<script>const options = {autoHideUI: 0, setAll: 0, rarityAll: 0};</script>";
		}

	}
	else {
		echo getForm($_GET);
			echo "<script>const options = {autoHideUI: 0, setAll: 0, rarityAll: 0};</script>";
	}


?>


<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' href='style\datatables.min.css'/>
	<link rel='stylesheet' href='style\jquery-ui.min.css'/>
	<link rel='stylesheet' href='style\style.css'/>
	<script src="libs\jquery-2.1.1.min.js"></script>
	<script src='libs\jquery-ui.min.js'></script>
	<script src='libs\datatables.min.js'></script>
	<script src='libs\Chart.bundle.min.js'></script>
	<script src='libs\hover.js'></script>
	<script src='script.js'></script>
	<script src='localChart.js'></script>
</head>
	<body>
	</body>
</html>
<script>
</script>

<style>
	body {
		font-size: 12px;
	}
	
	div input[type=number] {
		width: 60px;
	}

</style>
<?php

	include_once(__DIR__."/global.php");


	//buildFullCardPool(); return;
	
	echo '<a href="shakers.php">Reload Blank</a>';
	echo '<a style="margin-left: 100px; color: red;" href="charts.php">Go to single card lookup</a>';
		
		

	if (sizeof($_GET)){
		if (isset($_GET["rarities"]) && isset($_GET["foil"]) && isset($_GET["depth"]) && isset($_GET["sets"])){

			echo getForm($_GET);

			$time = time();
			$time = -microtime(true);

			$depth = $_GET["depth"];
			$minAvail = $_GET["minAvail"];
			$maxAvail = $_GET["maxAvail"];
			$minPrice = $_GET["minPrice"];
			$maxPrice = $_GET["maxPrice"];
			$availChange = $_GET["availChange"];
			$stackDisplay = 0; if (isset($_GET["stackDisplay"])){$stackDisplay = 1;}
			$type = $_GET["compareType"];
			$foil = 0; if ($_GET["foil"] == "Foil"){$foil = 1;}

			echo requestShakers(
				$_GET["sets"],
				$_GET["rarities"],
				$foil,
				$depth,
				$minAvail,
				$maxAvail,
				$minPrice,
				$maxPrice,
				$availChange,
				$stackDisplay,
				$type
			);


			$time += microtime(true);
			echo "Script Execution Completed; TIME:".round($time, 2)." seconds.";
		}
		else {
			echo "<div style='color: red; font-size: 30px'>Brah, no sets -> no results ... !</div>";
			echo getForm(array());
		}

	} else echo getForm($_GET);
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
	<script src='localChart.js'></script>
</head>
	<body>
	</body>
</html>
<script>
	function preset(arg){
		console.log("preset!");
		console.log(arguments);
	}
</script>
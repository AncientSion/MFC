<?php

	include_once(__DIR__."/global.php");


	echo getForm();


	if (sizeof($_GET)){
		if (isset($_GET["rarities"]) && isset($_GET["foil"]) && isset($_GET["sets"])){
			

			/*
			var_export($_GET["rarities"]);
			echo "</br>";
			var_export($_GET["foil"]);
			echo "</br>";
			var_export($_GET["sets"]);
			*/

			$time = time();
			$time = -microtime(true);

			$depth = 1;
			$minPrice = 10;
			$maxPrice = 0;
			$availChange = 0;
			$type = "pct";


			echo requestShakers(
				$_GET["sets"],
				$_GET["rarities"],
				$_GET["foil"],
				$depth,
				$minPrice,
				$maxPrice,
				$availChange,
				$type
			);


			$time += microtime(true);
			echo "Script Execution Completed; TIME:".round($time, 2)." seconds.";


		}
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
</head>
	<body>
	<?php
	?>
	</body>
</html>

<script>
$(document).ready(function(){
	$(".moveTable").each(function(){
		if (!this.childNodes[1].childNodes.length){
			$(this).remove();// return;
		}
		$(this).DataTable({
			"paging": false
		})
	})
})
</script>
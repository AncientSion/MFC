<?php

	include_once(__DIR__."/global.php");


	if (sizeof($_GET)){
		if (isset($_GET["rarities"]) && isset($_GET["foil"]) && isset($_GET["depth"]) && isset($_GET["sets"])){

			echo getForm($_GET);

			$time = time();
			$time = -microtime(true);

			$depth = $_GET["depth"];
			$minPrice = $_GET["minPrice"];
			$maxPrice = $_GET["maxPrice"];
			$availChange = $_GET["availChange"];
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
</head>
	<body>
	<?php
	?>
	</body>
</html>

<script>
$(document).ready(function(){
	window.options = {
		setAll: 0,
		rarityAll: 0
	}

	$(".moveTable").each(function(){
		if (!this.childNodes[1].childNodes.length){
			$(this).remove();// return;
		}
		$(this).DataTable({
			"paging": false
		})
	})

	$(document).contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
	})


	$("#rarity").contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
		if (options.rarityAll){
			options.rarityAll = 0;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.rarityAll);
			})
		}
		else {
			options.rarityAll = 1;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.rarityAll);
			})
		}
	})

	$("#set").contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
		if (options.setAll){
			options.setAll = 0;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.setAll);
			})
		}
		else {
			options.setAll = 1;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.setAll);
			})
		}
	})
})
</script>
<?php

	include_once(__DIR__."/global.php");

	if (sizeof($_GET)){
		if (isset($_GET["rarities"]) && isset($_GET["foil"]) && isset($_GET["sets"])){
			var_export($_GET["rarities"]);
			echo "</br>";
			var_export($_GET["foil"]);
			echo "</br>";
			var_export($_GET["sets"]);
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


		echo "<form method='get'>";

		$sets = json_decode(file_get_contents(__DIR__."/input/sets.json"), TRUE);
		$codes = $sets["codes"];
		$names = $sets["codes"];

		$rarityStr = array("Common", "Uncommon", "Rare", "Mythic Rare", "Special");
		$rarity = array("C", "U", "R", "M", "S");
		for ($i = 0; $i < sizeof($rarity); $i++){
			echo "<div class='checkContainer'>";
			echo "<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' checked='checked'>";
			echo "<span>".$rarityStr[$i]."</br>";
			echo "</div>"; 
		}

		echo "</br>";
		echo "<div class='checkContainer'><input type='checkbox' name='foil' checked='checked'>";
		echo "<span>Only Foil</span>";
		echo "</div>";
		echo "</br>";

		for ($i = 0; $i < sizeof($codes); $i++){
			for ($j = 0; $j < sizeof($codes[$i]); $j++){
				echo "<div class='checkContainer'><input type='checkbox' name='sets[]' value='".$codes[$i][$j]."'checked='checked'>";
				echo "<span>".$codes[$i][$j]."</span>";
				echo "</div>";
			}
			echo "</br>";
		}

		echo "<input type='submit' style='width: 200px' value='Search'></input>";

		echo "</form>";
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
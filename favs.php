<?php
	include_once(__DIR__."/global.php");

?>


<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' href='style\style.css'/>
	<link rel='stylesheet' href='style\jquery-ui.min.css'/>
	<link rel='stylesheet' href='style\Chart.min.css'/>
	<script src="libs\jquery-2.1.1.min.js"></script>
	<script src='libs\jquery-ui.min.js'></script>
	<script src='libs\Chart.bundle.min.js'></script>	
	<script src='script.js'></script>
</head>
	<body>
		<?php

			$favs = array(
				array("A25", "Brainstorm", 1),
				array("KLD", "Saheeli Rai", 0),
				array("KLD", "Saheeli Rai", 1),
				array("Shadows over Innistrad", "Second Harvest", 1),
				array("Shadows over Innistrad", "Cryptolith Rite", 1),
				array("Eternal Masters", "Sylvan Library", 1),
				array("Core 2019", "Scapeshift", 0),
				array("Core 2019", "Crucible of Worlds", 0),
			);

			$cont = "<div class='mainContainer'>";
			$foil = "<div class='container'>
						<canvas id='foilAvailCanvas'</canvas>
					</div>";
			$nonFoil = "<div class='container'>
							<canvas id='baseAvailCanvas'</canvas>
						</div>";


			foreach ($favs as $fav){
				echo $cont;

				echo 
					"<div class='ui disabled'>
						<div>
							<input type='form' value='".$fav[0]."'>
							<input type='form' value='".$fav[1]."'>
						</div>
					</div>";

				if ($fav[2] == 1){
					echo $foil;
				} else echo $nonFoil;

				echo "</div>";
			}
		?>

	</body>
</html>

<script type="text/javascript">
	
	window.onload = function(){
		charter = new Charter(1);

		things = [];

		$(".mainContainer").each(function(){
			chart = {}
			let can = $(this).find("canvas");
				can.each(function(){
					let type = $(this).attr("id");
						type = type.substr(0, type.length-6) + "Ctx";
					chart[type] = this.getContext("2d");
				})
				can.click(function(){
					let set = "";
					let card = "";
					$(this).parent().parent().find("input").each(function(i){
						if (!i){
							set = $(this).val();
						} else card = $(this).val()
					})
					console.log(set, card);

					window.open("charts.php?type=preset&set="+set+"&card="+card, '_blank');
				})

			charter.screens.push(chart);

			$(this).find("input").each(function(){
				things.push($(this).val())
			})
		})
		//console.log(things);

		timeout = setTimeout(function(){
			loadCharts();
		}, 1000);

		function loadCharts(){
			for (let i = 0; i < things.length; i+=2){
				//console.log(things[i], things[i+1]);
				charter.getCardData(i/2, things[i], things[i+1]);
			}
		}
	}

</script>

<style>	
	.mainContainer {
		display: inline-block;
		margin: auto;
		width: 500px;
	}
	.mainContainer .disabled {
		display: none;
	}
	.mainContainer div {
		width: 100%;
	}

</style>
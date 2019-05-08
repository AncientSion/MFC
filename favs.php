<?php
	include_once(__DIR__."/global.php");

	//DB::app()->dump(); return;

	if (isset($_POST["type"]) && $_POST["type"] == "addNewFavs"){
		echo "addNewFavs";

		//echo var_export($_POST); return;

		if (DB::app()->insertNewFavorites($_POST["sets"], $_POST["cards"], $_POST["isFoil"])){
			echo "added!";
		}

		return;
		
	}



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


		echo
			"<form>
				<table class='newEntryTable'>
					<thead>
						<tr>
							<th>set</th>
							<th>card</th>
							<th>foil</th>
						</tr>
					</thead>

					<tbody>
						<tr class='newEntryBlank'>
							<td colSpan=3>
								<div>
									<input type='form' class='setSearch'></input>
									<input type='form' class='cardSearch'></input>
									<input type='checkbox'></input>
								</div>
							</td>
						</tr>
					</tbody>

					<tfoot>
						<tr>
							<td colSpan=3>
								<input type='button' value='new row' onclick='addNewRow()'></input>
								<input type='button' value='Confirm' onclick='charter.assembleFavData()'></input>
							</td>
						</tr>
					</tfoot>
				</table>
			</form>";


			$favs = DB::app()->getFavorites();

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
					"<div class='disabled'>
						<div>
							<input type='form' value='".$fav["setcode"]."'>
							<input type='form' value='".$fav["cardname"]."'>
						</div>
					</div>";

				if ($fav["isFoil"]){
					echo $foil;
				} else echo $nonFoil;

				echo "</div>";
			}
		?>

	</body>
</html>

<script type="text/javascript">

	const charter = new Charter(1);
	
	window.onload = function(){
		addNewRow();
	}

	function addNewRow(){
		let table = $(".newEntryTable");
		let row = table.find(".newEntryBlank").clone();
			row.removeClass().find("div").addClass("search");

		//coremoveClass().find("div").addClass("search");
			table.append(row)
		charter.initCardSearchInputs(row)
	}


</script>

<style>	
	.mainContainer {
		display: inline-block;
		margin: auto;
		width: 400px;
	}
	.mainContainer .disabled {
		display: none;
	}
	.mainContainer div {
		width: 100%;
	}

	.newEntryTable input[type=button] {
	}

	.newEntryBlank {
		display: none;
	}

</style>
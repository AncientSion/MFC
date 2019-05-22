<?php
	include_once(__DIR__."/global.php");

	if (isset($_POST["type"])){
		if ($_POST["type"] == "addNewFavs"){
			if (DB::app()->insertFavorites($_POST["sets"], $_POST["cards"], $_POST["isFoil"])){
				echo "added!";
			}
			return;
		}
		else if ($_POST["type"] == "delNewFavs"){
			if (DB::app()->deleteFavorites($_POST["ids"])){
				echo "deleted!";
			}
			return;
		}
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

			$foil = "<div class='container'>
						<canvas id='foilAvailCanvas'</canvas>
					</div>";
			$nonFoil = "<div class='container'>
							<canvas id='baseAvailCanvas'</canvas>
						</div>";


			foreach ($favs as $fav){
				$id = "id".$fav['id'];
				echo "<div class='".$id." mainContainer'>";

				echo 
					"<div class='fakeSearch'>
						<div>
							<input type='form' value='".$fav["setcode"]."'>
							<input type='form' value='".$fav["cardname"]."'>
							<input type='button' value='Full' onclick=charter.linkToChartsPHP(this)>
							<input type='button' value='MKM' onclick=charter.linkToMKM(this)>
							<input type='button' value='DEL' onclick=charter.deleteSingleFavorite(this)>
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
		width: 300px;
		padding: 2px;
		height: unset;
	}

	.mainContainer div {
		width: 100%;
	}

	.newEntryTable {
		margin-bottom: 10px;
	}

	.newEntryBlank {
		display: none;
	}

	.fakeSearch input[type=form] {
		font-size: 8px;
		display: none;
	}

	.fakeSearch input:nth-child(1) {
		width: 40px;
	}
	.fakeSearch input:nth-child(2) {
		width: 60px;
	}

</style>
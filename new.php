<?php

include_once(__DIR__."\global.php");




if (php_sapi_name() == 'cli'){doCrawl(); return;}



if (sizeof($_POST)){
	//		var_export($_POST);
	if ($_POST["type"] == "analyzeFolder"){
		$codes = isset($_POST["codes"]) ? $_POST["codes"] : array();
		$format = $_POST["format"];


		glob::$activeFormat = $format;
		glob::$codes = $codes;
		glob::$depth = $_POST["depth"];

		$options = $_POST["options"];

		foreach ($options as $key => $value){
			//echo "".$key." => ".$value."</div>";
			glob::$options[$key] = $value;
		}

		processFolder();
	}
	else if ($_POST["type"] == "getTours"){
		glob::$activeFormat = $_POST["format"];
		getTours();
	}
	return;
}




class glob {
	static $cards = array();
	static $cardAmounts = array();
	static $types = array();
	static $typeAmounts = array();
	static $checkSB = 0;
	static $codes = array("WAR", "MHZ");
	static $formats = array("Modern", "Standard", "Legacy");
	static $depth = 0;
	static $tours = array();
	static $decks = array();
	static $activeFormat = "";
	static $allCards = 0;
	static $options = array(
		"topCards" => 0,
		"minCardShowing" => 0,
		"maxCardShowing" => 0,
		"minArchetype" => 0,
	);
	//static $lr = php_sapi_name() == "cli" ? "\n" : "</br>";

	static function reset(){
		self::$cards = array();
		self::$cardAmounts = array();
		self::$types = array();
		self::$typeAmounts = array();
	}

	static function doSort(){
		if (1){
			for ($i = sizeof(self::$cardAmounts)-1; $i >= 0; $i--){
				$name = self::$cards[$i];
				if ($name == "Forest" || $name == "Mountain" || $name == "Island" || $name == "Plains" || $name == "Swamp" || substr($name, 0, 4) == "Snow"){
					array_splice(self::$cards, $i, 1);
					array_splice(self::$cardAmounts, $i, 1);
				}
			}
		}
		array_multisort(self::$cardAmounts, self::$cards);
		array_multisort(self::$typeAmounts, self::$types);
	}
}




function doCrawl(){
	echo "*** Beginning ".LR.LR;

	for ($i = 0; $i < sizeof(glob::$formats); $i++){
		glob::$activeFormat = glob::$formats[$i];
		$url = "https://www.mtggoldfish.com/tournaments/".glob::$activeFormat."#paper";
		$html = file_get_html($url, false, CONTEXT);

		$headers = $html->find(".decks-sidebar h4 > a");

		for ($j = 1; $j < sizeof($headers); $j+=2){
			if (!processTour($headers[$j])){break;}
		}

		$html->clear(); 
		unset($html);
	}
}

function processTour($header){

	$prop = "href";
	$tourURL = "https://www.mtggoldfish.com/".$header->{$prop};

	$tourName = htmlspecialchars_decode($header->innertext);
	if (strlen($tourName) > 38){
		$tourName = substr($tourName, 0, 38);
		$tourName = str_replace(" ", "_", $tourName);
	}

	echo("checking ".glob::$activeFormat." tour: ".$tourName.LR);// return;
	//return;
	//msg("len: ".strlen(htmlspecialchars_decode($header->innertext))); return;

	$html = file_get_html($tourURL, false, CONTEXT);
	$rows = $html->find(".table-condensed", 0)->find("tr");

	$dateString = $html->find(".row", 0)->find("div > p", 1);
	$dateString = substr($dateString, sizeof($dateString)-16);
	$dateString = substr($dateString, 0, 10);

	$dir = dirname(__FILE__).'/lists/'.glob::$activeFormat.'/'.$dateString."_".$tourName.'/';
	if (!file_exists($dir) && !is_dir($dir)){
		echo("-> processing!".LR); mkdir($dir);  
	}
	else {
		echo("-> BREAK!".LR);
		return false;
	}

	$deckNameTDindex = sizeof($rows[1]->children()) == 6 ? 1 : 2;

	for ($i = 1; $i < sizeof($rows); $i+=2){
		//echo sizeof($rows).LR;
		$a = $rows[$i]->find("td", $deckNameTDindex)->find("a", 0);
		$deckTitle = htmlspecialchars_decode($a->innertext, 1);
		$deckidString = $a->{$prop};
		$deckid = substr($deckidString, 6, strlen($deckidString));
		$downloadURL = "https://www.mtggoldfish.com/deck/download/".$deckid;
		handleDecklist($dir, $deckTitle, $downloadURL, $deckid);
	}

	$html->clear(); 
	unset($html);
	return true;
}



function handleDeckList($targetFolder, $deckName, $url, $deckid){
	$targetName = $targetFolder.str_replace("/", "", $deckName)."_".$deckid.'.txt';
	if (file_exists($targetName)){return;}
	file_put_contents($targetName,  fopen($url, "r"));
	return;
}

function getTours(){
	$file = null;
	$dir = __DIR__.'/lists/'.glob::$activeFormat."/";
	$tours = scandir($dir);

	$tours = array_slice($tours, 2);
	//$tours = array_slice($tours, -glob::$depth);
	$tours = array_slice($tours, -10);
	$tours = array_reverse($tours);


	foreach ($tours as $tour){
		//if (substr($tour, strlen($tour)-3) == "..."){continue;}
		echo "<tr><td><input type='checkbox' checked='checked'>".$tour."</td></tr>";
	}
}


function processFolder(){

	$file = null;
	$dir = __DIR__.'/lists/'.glob::$activeFormat."/";
	$tours = scandir($dir);

	$tours = array_slice($tours, 2);
	$tours = array_slice($tours, -glob::$depth);
	$tours = array_reverse($tours);


	foreach ($tours as $tour){
		if (substr($tour, strlen($tour)-3) == "..."){continue;}

		$decks = scandir($dir.$tour);

		glob::$tours[] = $tour." <span class='yellow'>(".(sizeof($decks)-2)." decks)</span>";
		glob::$decks[] = sizeof($decks)-2;

		foreach ($decks as $deck){
			if ($deck == ".." || $deck == "."){continue;}
			readDeckFile($tour, $deck);
			checkArcheType($deck);
		}
		//return;
	}

	//die();
	readResults();
	return;
}

function checkArchetype($deck){
	$name = substr($deck, 0, strpos($deck, "_"));
	//echo "deck type ".$name.LR;
	$found = false;

	for ($i = 0; $i < sizeof(glob::$types); $i++){ // arches
		if ($name == glob::$types[$i]){
			glob::$typeAmounts[$i] += 1;
			$found = true;
			break;
		}
	}
	if (!$found){
		glob::$types[] = $name;
		glob::$typeAmounts[] = 1;
	}
}

function readDeckFile($tour, $deck){
	$folderString = __DIR__."/lists/".glob::$activeFormat."/".$tour."/".$deck;
	//echo "read: ".$folderString.LR;
	//die();

	$list = fopen($folderString, "r");

	while(!feof($list)){
		$found = false;
		$str = fgets($list);
		if (strlen($str) == 2){if (glob::$checkSB){continue;} else break;}
		$breakpoint = strpos($str, " ");
		$amount = intval(substr($str, 0, $breakpoint));
		$name = substr($str, $breakpoint+1, strlen($str)-3-$breakpoint);

		for ($i = 0; $i < sizeof(glob::$cards); $i++){ // cards
			if ($name == glob::$cards[$i]){
				glob::$cardAmounts[$i] += $amount;
				$found = true;
				break;
			}
		}
		if (!$found){
			glob::$cards[] = $name;
			glob::$cardAmounts[] = $amount;
		}

		glob::$allCards += $amount;
	}

	fclose($list);
}

function readResults(){

	//echo "results!".LR;

	echo "<div class='resultWrapper'>";

		echo("analyzing <span class='yellow'>".glob::$activeFormat."</span>, ".(glob::$checkSB ? "Sideboard: ON" : "Sideboard OFF").LR.LR);
		echo("Tours: ".LR.implode(glob::$tours, LR).LR.LR);
		echo(array_sum(glob::$decks)." decks".LR);
		echo("total cards: ".glob::$allCards.LR);

		glob::doSort();

		//var_export(glob::$options); die();

		if (glob::$options["topCards"]){
			postAnyTopCards(glob::$options["topCards"]);
		}
		else if (glob::$options["minCardShowing"]){
			if (glob::$options["maxCardShowing"]){
				postRangeCards(glob::$options["minCardShowing"], glob::$options["maxCardShowing"]);
			}
			else postTopBySet(glob::$options["minCardShowing"]);
		}
		
		if (glob::$options["minArchetype"]){
			postArchetypes(glob::$options["minArchetype"]);
		}
	echo "</div>";
}




function postArchetypes($toShow){

	//var_export(glob::$typeAmounts);
	echo("<div class='row'><span class='yellow'>Showing archetypes with min ".$toShow." showings</span>".LR);

	$entries = sizeof(glob::$typeAmounts)-1;

	for ($i = $entries; $i >= 0; $i--){
		if (glob::$typeAmounts[$i] < $toShow){continue;}
		echo(glob::$typeAmounts[$i]."x ".glob::$types[$i].LR);
	}
	echo("</div>");
}

function postTopBySet($tresh){
	echo("<div class='row'><span class='yellow'>Showing top cards, min ".$tresh." uses.".LR."Only ".implode(glob::$codes, " - ")."</span>".LR);
	$db = DB::app();
	$setTable = $db->getPickedSetNames(glob::$codes);
	$sets = $db->getAllCardsBySetTables($setTable);
	for ($i = sizeof(glob::$cardAmounts)-1; $i >= 0; $i--){
		if (glob::$cardAmounts[$i] <= $tresh){continue;}
		foreach ($sets as $set){
			foreach ($set["cards"] as $card){
				if (glob::$cards[$i] == $card["cardname"]){
					echo(glob::$cardAmounts[$i]."x ".glob::$cards[$i]." <span class='yellow small'>".$set["setcode"]."</span>".LR);
				}
			}
		}
	}
	echo("</div>");
}

function postAnyTopCards($toShow){
	echo("<div class='row'><span class='yellow'>Showing top ".$toShow." cards, any edition</span>".LR);
	$entries = sizeof(glob::$cardAmounts)-1;
	$post = $entries-$toShow;

	for ($i = $entries; $i > $post; $i--){
		echo(glob::$cardAmounts[$i]."x ".glob::$cards[$i].LR);
	}
	echo("</div>");
}

function postRangeCards($min, $max){
	echo("<div class='row'><span class='yellow'>Showing cards with range ".$min." - ".$max.", any edition</span>".LR);
	$entries = sizeof(glob::$cardAmounts)-1;
	//$post = $entries-$toShow;

	for ($i = sizeof(glob::$cardAmounts)-1; $i >= 0; $i--){
		if (glob::$cardAmounts[$i] > $max){continue;}
		if (glob::$cardAmounts[$i] < $min){break;}

		echo(glob::$cardAmounts[$i]."x ".glob::$cards[$i].LR);
	}
	echo("</div>");
}


?>

<!DOCTYPE html>
<html>
<head>
</head>
	<body>
		<div>
			<?php


			echo 
				"<table class='newEntryTable'>
					<thead>
						<tr>
							<th>set</th>
						</tr>
					</thead>

					<tbody>
						<tr class='newEntryBlank'>
							<td>
								<div>
									<input type='form' class='setSearch'></input>
								</div>
							</td>
						</tr>
					</tbody>

					<tfoot>
						<tr>
							<td>
								<input type='button' value='new row' onclick='charter.addNewRow()'></input>
							</td>
						</tr>
					</tfoot>
				</table>";


					
		/*		$file = null;
				//echo ; die();
				$dir = __DIR__.'/lists/'."Modern"."/";
				$tours = scandir($dir);
				$tours = array_slice($tours, 2);
				$tours = array_reverse($tours);

				echo "<table>";

				foreach ($tours as $tour){
					echo "<tr>";
					echo "<td>";
					echo "<input type='checkbox' checked='checked'>";
					echo "</td>";
					echo "<td>".$tour."</td>";
					echo "</tr>";
				}
				echo "</table>";
		*/


				echo "<select onChange='updateTours()'>";
				foreach (glob::$formats as $format){
					echo "<option value='".$format."'>".$format."</option>";
				}
				echo "</select>";


				$tours = sizeof(scandir(__DIR__.'/lists/'."Modern"."/"))-2;
				echo "<div class='option recentTours'>Analyze last<input type='number' value='6' min='1'> tours (max: ".$tours.")</div>";
				echo "<div class='option topCards'><input type='checkbox' checked='checked'>Show top<input type='number' value='24'> cards, any set</div>";
				echo "<div class='option minCardShowing'><input type='checkbox'>Min card showing<input type='number' value='12'>, picked set</div>";
				echo "<div class='option maxCardShowing'><input type='checkbox'>Max card showing<input type='number' value='32'>, picked set</div>";
				echo "<div class='option minArchetype'><input type='checkbox'>Show archetypes with min <input type='number' value='4'> showings</div>";

			?>
			<input type='button' value='analyze' onclick='charter.analyze()'>

			<table id='tourList'></table>
		</div>
	</body>
</html>

<script src="libs\jquery-2.1.1.min.js"></script>
<link rel='stylesheet' href='style\jquery-ui.min.css'/>
<script src='libs\jquery-ui.min.js'></script>
<script src='script.js'></script>

<script type="text/javascript">

	const charter = new Charter();
	window.onload = function(){
		charter.addNewRow();
		charter.addRecentTours("Modern");
	}

	function updateTours(){
		console.log("updateTours");
		charter.addRecentTours($("select :selected").text());
	}

</script>

<style>

	body {
		background-color: black;
		color: white;
		font-family: arial;
	}

	body, span {
		font-size: 14px;
	}

	.newEntryBlank {
		display: none;
	}

	.red {
		color: red;
	}

	.yellow {
		color: yellow;
	}
	.small {
		font-size: 10px
	}

	div.wrapper {
		display: flex;
	}

	div > div {
		margin-top: 10px;
		display: inline-block;
		padding: 15px;
		margin-left: 20px;
	}

	.tour, .option {
		padding: unset;
		margin: unset;
		display: block;
	}

	input[type=number], input[type=text] {
	  width: 35px;
	}

	input[type=checkbox] {
		transdform: scale(1);
	}
	
	div.option {
		margin-top: 10px;
		margin-bottom: 10px;
	}

	div.option input[type=number] {
		margin-left: 5px;
		margin-right: 5px;
	}

</style>
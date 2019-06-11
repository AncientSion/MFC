<?php


header('Content-Type: text/html; charset=utf-8');
include_once(__DIR__."\global.php");
include_once(__DIR__."\libs\dump.php");

define("CONTEXT", stream_context_create(
	    array(
	        "http" =>
				array(
				   "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	)
);





//crawl();
processFolder();


class glob {
	static $cards = array();
	static $cardAmounts = array();
	static $types = array();
	static $typeAmounts = array();
	static $checkSB = 0;
	static $codes = array("WAR", "MHZ");
	static $formats = array("Standard", "Modern");
	static $tours = array();
	static $decks = array();
	static $activeFormat = "";
	static $allCards = 0;
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
				if ($name == "Forest" || $name == "Mountain" || $name == "Island" || $name == "Plains" || $name == "Swamp"){
					array_splice(self::$cards, $i, 1);
					array_splice(self::$cardAmounts, $i, 1);
				}
			}
		}
		array_multisort(self::$cardAmounts, self::$cards);
		array_multisort(self::$typeAmounts, self::$types);
	}
}




function crawl(){

	echo "\n\n*** Beginning \n";

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

//define("DATE", date('Y.m.d', time()));


function processTour($header){

	$prop = "href";
	$tourURL = "https://www.mtggoldfish.com/".$header->{$prop};

	$tourName = htmlspecialchars_decode($header->innertext);
	if (strlen($tourName) > 38){
		$tourName = substr($tourName, 0, 38);
	}

	echo("checking ".glob::$activeFormat." tour: ".$tourName.LR);// return;
	//msg("len: ".strlen(htmlspecialchars_decode($header->innertext))); return;

	$html = file_get_html($tourURL, false, CONTEXT);
	$rows = $html->find(".table-condensed", 0)->find("tr");

	$dateString = $html->find(".row", 0)->find("div > p", 1);
	$dateString = substr($dateString, sizeof($dateString)-16);
	$dateString = substr($dateString, 0, 10);

	$dir = dirname(__FILE__).'/lists/'.glob::$activeFormat.'/'.$dateString."_".$tourName.'/';
	if (!file_exists($dir) && !is_dir($dir)){
		echo("processing!".LR); mkdir($dir);  
	}
	else {
		echo("BREAKING!".LR);
		return false;
	}

	$deckNameTDindex = sizeof($rows[1]->children()) == 6 ? 1 : 2;

	for ($i = 1; $i < sizeof($rows); $i+=2){
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


function processFolder(){
	glob::$activeFormat = "Modern";
	$file = null;
	//echo ; die();
	$dir = __DIR__.'/lists/'.glob::$activeFormat."/";
	$tours = scandir($dir);
	$tours = array_slice($tours, sizeof($tours)-3);
	$tours = array_reverse($tours);

	//msg("FORMAT: ".glob::$activeFormat); return;

	foreach ($tours as $tour){
		if (substr($tour, strlen($tour)-3) == "..."){continue;}

		$decks = scandir($dir.$tour);
		$decks = array_slice($decks, 2);

		glob::$tours[] = $tour." <span class='yellow'>(". sizeof($decks)." decks)</span>";
		glob::$decks[] = sizeof($decks);

		foreach ($decks as $deck){
			readDeckFile($tour, $deck);
			checkArcheType($deck);
		}
		//return;
	}

	readResults();
	return;
}

function checkArchetype($deck){
	$name = substr($deck, 0, strpos($deck, "_"));
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

	echo("<div>");
	echo("analyzing <span class='yellow'>".glob::$activeFormat."</span>, ".(glob::$checkSB ? "Sideboard: ON" : "Sideboard OFF").LR.LR);
	echo("Tours: ".LR.implode(glob::$tours, LR).LR.LR);
	echo(array_sum(glob::$decks)." decks".LR);
	echo("total cards: ".glob::$allCards.LR);
	echo("</div>");

	glob::doSort();

	echo "<div class='wrapper'>";
	postArchetypes(2);
	//postAnyTopCards(48);
	//postRangeCards(4, 8);
	postTopBySet(4);
	echo "</div>";
}

function postArchetypes($toShow){	
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
	$post = $entries-$toShow;

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
	</body>
</html>
<script>
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

</style>
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include(__DIR__."/simple_html_dom.php");
include(__DIR__."/server/db.php");

header('Content-Type: text/html; charset=utf-8');
define ("LR", php_sapi_name() == "cli" ? "\n" : "</br>");
define("CONTEXT", stream_context_create(
	    array(
	        "http" =>
				array(
				   "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	)
);



function requestCardText($cardname, $set){
	$folder = 'input';
	$file = $set.".json";
	$data = file_get_contents($folder."/".$file);
	$data = json_decode($data);

	foreach ($data->cards as $card){
		if ($card->name == $cardname){
			return "<div>".$card->text."</div>";
		}
	}
	return "not found!";
}

function getMKMURL($set, $card){
	
	if (strlen($set) > 5 && substr($set, strlen($set)-5, 5) == "Boxes"){
		$base = "https://www.cardmarket.com/en/".substr($set, 0, strlen($set)-6)."/Products/Booster+Boxes/";
		$card = str_Replace(")", "", str_replace("(", "", str_replace("-//", "", preg_replace("/ /", "-", preg_replace("/'/", "", preg_replace("/,/", "", $card))))));
		return $base.$card;
	}
	else {
		$base = "https://www.cardmarket.com/en/Magic/Products/Singles/";
		$set =  doReplace($set);
		//debug($set);
		$card = str_replace("ö", "oe", str_replace("ä", "ae", str_replace("ü", "ue", $card)));
		$card = str_replace("-/-", "-", str_replace("-//", "", preg_replace("/ /", "-", preg_replace("/'/", "-", preg_replace("/,/", "", $card)))));
		$card = str_replace(")", "", str_replace("(", "", str_replace(":", "", $card)));
		//echo $card."</br>";
		$url = $base.$set."/".$card;
		return $url;
	}
}


function doReplace($set){
	return str_replace(":", "", preg_replace("/--/", "-", preg_replace("/&/", "", preg_replace("/ /", "-", preg_replace("/'/", "", preg_replace("/,/", "", $set))))));
}

function getMemory(){
	$size = memory_get_usage(true);
    $unit = array('b','kb','mb','gb','tb','pb');
    return (@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]);
}

function getForm($get){
	//var_export($get);

	$db = DB::app();

	$lastPull = $db->getLastPullDate();

	$html = "";

	$html .="<div class='upper'>";
	$html .='<div class="lower yellow">'.$lastPull.'</div>';
	$html .='<div class="lower"><a href="shakers.php">Reload Blank</a></div>';
	$html .='<div class="lower"><a href="charts.php" target="_blank">Single lookup</a></div>';
	$html .='<div class="lower"><a href="favs.php" target="_blank">Favs</a></div>';
	$html .='<div class="lower"><a href="helper.php" target="_blank">help</a></div>';
	$html .='<div class="lower"><input type="button" value="hide" onclick="toggleUI()"></div>';
	$html .='<div class="lower"><input type="button" value="load pics" onclick="charter.toggleLoadPics()"></div>';
	$html .="</div>";


	$html .="<form class='upper' method='get'>"; 

	$foilChecked = "";
	$nonFoilChecked = "";
	if (sizeof($get)){
		if ($get["foil"] == "Is Foil"){$foilChecked = "checked='checked'";}
		else if ($get["foil"] == "Not Foil"){$nonFoilChecked = "checked='checked'";}
	}
	else {
		$foilChecked = "checked='checked'";
	}

	//$html .= 

	$html .="<div class='checkWrapper'>";

	$html .="<div class='optionWrapper'>";

	$html .="<div class='inputContainer'>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Is Foil'".$foilChecked."'>Is Foil</div>";
	$html .="<div class='checkContainer'><input type='radio' name='foil' value='Not Foil'".$nonFoilChecked."'>Not Foil</div>";
	$html .= "</div>";

	
	
	$pctChecked = "";
	$absChecked = "";
	if (sizeof($get)){
		if ($get["compareType"] == "PCT"){$pctChecked = "checked='checked'";}
		else if ($get["compareType"] == "ABS"){$absChecked = "checked='checked'";}
	}
	else {
		$pctChecked = "checked='checked'"; 
	}

	$html .="<div class='inputContainer'>";
	$html .="<div class='checkContainer'><input type='radio' name='compareType' value='PCT'".$pctChecked."'>pct</div>";
	$html .="<div class='checkContainer'><input type='radio' name='compareType' value='ABS'".$absChecked."'>abs</div>";
	$html .= "</div>";



	$depth = 1;
	if (sizeof($get)){$depth = $get["depth"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Depth</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='-999' max='999' value='".$depth."' name='depth'>";
	$html .= "</div>";
	$html .= "</div>";

	$minAvail = 0;
	if (sizeof($get)){$minAvail = $get["minAvail"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Min Avail (n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min='0' max='10000' value='".$minAvail."' name='minAvail'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxAvail = 0;
	if (sizeof($get)){$maxAvail = $get["maxAvail"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='depth'>Max Avail (n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=10000 value='".$maxAvail."' name='maxAvail'>";
	$html .= "</div>";
	$html .= "</div>";

	$minPrice = 0;
	if (sizeof($get)){$minPrice = $get["minPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='minPrice'>Min (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=5000 step=0.5 value='".$minPrice."' name='minPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$maxPrice = 0;
	if (sizeof($get)){$maxPrice = $get["maxPrice"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='maxPrice'>Max (€, n)</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=0 max=5000 value='".$maxPrice."' name='maxPrice'>";
	$html .= "</div>";
	$html .= "</div>";

	$availChangeMin = -14;
	if (sizeof($get)){$availChangeMin = $get["availChangeMin"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='availChangeMin'># Min Change</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=-100 max=100 value='".$availChangeMin."' name='availChangeMin'>";
	$html .= "</div>";
	$html .= "</div>";

	$availChangeMax = 0;
	if (sizeof($get)){$availChangeMax = $get["availChangeMax"];}
	$html .="<div class='inputContainer'>";
	$html .="<div id='availChangeMax'># Max Change</div>";
	$html .="<div class=''>";
	$html .= "<input type='number' min=-100 max=100 value='".$availChangeMax."' name='availChangeMax'>";
	$html .= "</div>";
	$html .= "</div>";

	
	$checked = "";
	if (sizeof($get) && isset($get["plusminus"])){
		$checked = "checked='checked'";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='plusminus' value='1'".$checked."'>";
	$html .= "+/-</div>";
	
	$stackDisplay = "";
	$checked = "";
	$value = "";
	//var_export($get);
	if (sizeof($get) && isset($get["stackDisplay"])){
		$checked = "checked='checked'";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='stackDisplay' value='1'".$checked."'>";
	$html .= "stack</div>";
	
	
	$skipUnchanged = "";
	$checked = "checked='checked'";
	$value = "";
	//var_export($get);
	if (sizeof($get) && !isset($get["skipUnchanged"])){
		$checked = "";
	}
	$html .="<div class='checkContainer'>";
	$html .= "<input type='checkbox' name='skipUnchanged' value='1'".$checked."'>";
	$html .= "Skip 0</div>";

	$html .= "</div>";
	$html .= "</div>";

	

	$html .="<div class='checkWrapper'>";
	//$html .="<div id='set' class='toggle'>Sets to include</div>";
//	$sets = json_decode(file_get_contents(__DIR__."/output/avail.json"), TRUE);
//	$codes = $sets["codes"];
//	$names = $sets["codes"];

	$sets = $db->getAllSets();

	usort($sets, function ($a, $b){
		if (substr($a["setcode"], 0, 1) == "_"){
			return 1;
		}
		else if ($a["type"] != $b["type"]){
			return $a["type"] - $b["type"];
		}
		else if ($a["foil"] != $b["foil"]){
			return $a["foil"] - $b["foil"];
		}
		else if ($a["nonfoil"] != $b["nonfoil"]){
			return $a["nonfoil"] - $b["nonfoil"];
		}
		else return substr($a["setcode"], 0, 1) > substr($b["setcode"], 0, 1);
	});

//for ($i = 0; $i < sizeof($sets); $i++){echo $sets[$i]["setcode"]." ".$sets[$i]["foil"]." ".$sets[$i]["nonfoil"]."</br>";}die();

	$foil = -1;
	$nonfoil = -1;

	for ($i = 0; $i < sizeof($sets); $i++){
		if ($sets[$i]["foil"] != $foil || $sets[$i]["nonfoil"] != $nonfoil){
			$foil = $sets[$i]["foil"];
			$nonfoil = $sets[$i]["nonfoil"];

			if ($i){$html .= "</div>";}
			$html .= "<div class='setDivider'>";
		}

		$checked = "";
		if (sizeof($get) && $get["sets"]){
			for ($j = 0; $j < sizeof($get["sets"]); $j++){
				if ($get["sets"][$j] == $sets[$i]["setcode"]){
					$checked = "checked='checked'";
				}
			}
		}

		$html .="<div class='checkContainer set'><input type='checkbox' name='sets[]' value='".$sets[$i]["setcode"]."' ".$checked.">";
		$html .="<span>".$sets[$i]["setcode"]."</span>";
		$html .="</div>";
	}
	$html .= "</div>";
	$html .= "</div>";



	$rarity = array("C", "U", "R", "M", "S");
	$preset = array('', '', "checked='checked'", "checked='checked'", "checked='checked'");
	$html .="<div class='checkWrapper'>";
	$html .="<div class='optionWrapper'>";

	if (sizeof($get) && $get["rarities"]){
		for ($i = 0; $i < sizeof($rarity); $i++){
			$checked = '';
			for ($j = 0; $j < sizeof($get["rarities"]); $j++){
				if ($get["rarities"][$j] == $rarity[$i]){
					$checked = "checked='checked'";
				}
			}

			$html .="<div class='checkContainer'>";
			$html .="<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' ".$checked.">";
			$html .="<span>".$rarity[$i]."</br>";
			$html .="</div>";
		}
	}
	else {
		for ($i = 0; $i < sizeof($rarity); $i++){
			$html .="<div class='checkContainer'>";
			$html .="<input type='checkbox' name='rarities[]' value='".$rarity[$i]."' ".$preset[$i].">";
			$html .="<span>".$rarity[$i]."</br>";
			$html .="</div>";
		} 
	}
	
	$html .="<div style='display: inline-block'><input type='submit' style='width: 100px; font-size: 20px' value='Search'></input></div>";
	$html .="</div>";
	$html .="</div>";
	$html .="</form>";
	return $html;
}

function debug($string){
	//echo $string;
	file_put_contents(__DIR__."/debug.log", $string.PHP_EOL, FILE_APPEND);
}

function logShakers($codes, $rarities, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChangeMin, $plusminus, $stackDisplay, $skipUnchanged, $compareType){
	return;
	$stamp = time();

	$search = array(
		"type" => "compare",
		"stamp" => $stamp,
		"date" => date('d.m.Y', $stamp),
		"time" => date('H:i:s', $stamp),
		"options" => array(
			$codes, $rarities, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChangeMin, $plusminus, $stackDisplay, $skipUnchanged, $compareType
		)
	);

	file_put_contents(__DIR__."/search.log", json_encode($search, JSON_NUMERIC_CHECK).",\n", FILE_APPEND);
}

function isValidSetForSearchOptions($set, $foil){
	//var_export($set); echo LR; return true;
	if ($set["lastPull"] != "0000-00-00" && ($set["foil"] && $foil || $set["nonfoil"] && !$foil)){
		return true;
	}
	//echo "skipping ".$set["setname"];
	return false;
}

function requestAllShakers($codes, $rarities, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChangeMin, $availChangeMax, $plusminus, $stackDisplay, $skipUnchanged, $compareType){

	$time = time();
	$date = date('d.m.Y', $time);
	$time = -microtime(true);
	
	//mored Ascensecho var_export(func_get_args());

	logShakers($codes, $rarities, $foil, $depth, $minAvail, $maxAvail, $minPrice, $maxPrice, $availChangeMin, $availChangeMax, $plusminus, $stackDisplay, $skipUnchanged, $compareType);

	$db = DB::app();


	$setnames = $db->getPickedSetNames($codes);

	for ($i = sizeof($setnames)-1; $i >= 0; $i--){
		if (!isValidSetForSearchOptions($setnames[$i], $foil)){
			array_splice($setnames, $i, 1);
			array_splice($codes, $i, 1);
		}
	}

	//var_export($codes); return;

	$data = $db->getAllPickedCardsForShakersFromDB($codes, $rarities);
	$db->getBulkChartData($codes, $data, $depth);

	//var_export($data); die();

	//foreach ($data[0][0]["points"] as $point){var_export($point); echo "</br>";}

	$allData = array();

	for ($i = 0; $i < sizeof($data); $i++){
		if (!(isset($data[$i][0]))){continue;}

		$extract = array(
			"setname" => $setnames[$i]["setname"],
			"setcode" => $codes[$i],
			"compareDate" => $data[$i][0]["points"][0]["date"],
			"lastDate" => $data[$i][0]["points"][sizeof($data[$i][0]["points"])-1]["date"],
			"shakers" => array()
		);

		//var_export($extract);
		//echo $maxAvail;
		//msg(sizeof($data[$i]));

		for ($j = sizeof($data[$i])-1; $j >= 0; $j--){
			$splice = 0;

			//var_export($data[$i][$j]);
			$last = $data[$i][$j]["points"][sizeof($data[$i][$j]["points"])-1];

			if ($minAvail && ((!$foil && $last["baseAvail"] < $minAvail) || ($foil && $last["foilAvail"] < $minAvail))){$splice = 1;}
			else if ($maxAvail && ((!$foil && $last["baseAvail"] > $maxAvail) || ($foil && $last["foilAvail"] > $maxAvail))){$splice = 1;}
			else if ($minPrice && ((!$foil && $last["basePrice"] < $minPrice) || ($foil && $last["foilPrice"] < $minPrice))){$splice = 1;}
			else if ($maxPrice && ((!$foil && $last["basePrice"] > $maxPrice) || ($foil && $last["foilPrice"] > $maxPrice))){$splice = 1;}

			if ($splice){
				//msg("splicing ".$data[$i][$j]["cardname"].", avail: ".$last["foilAvail"]);
				array_splice($data[$i], $j, 1);
			}
		}

	//	msg(sizeof($data[$i]));
		$extract["shakers"] = $data[$i];
		$allData[] = $extract;
	}
	//foreach ($data[0][0]["points"] as $point){var_export($point); echo "</br>";} die();

	for ($i = 0; $i < sizeof($allData); $i++){
		for ($j = sizeof($allData[$i]["shakers"])-1; $j >= 0; $j--){
			setChangeValue($allData[$i]["shakers"][$j], $foil);
		}
	}

	//var_export($allData); die();


	$stackDisplay = ($stackDisplay && $depth && $depth < 11);

	$time += microtime(true);
	//debug("Script Execution Completed; TIME:".round($time, 2)." seconds, memory: ".getMemory());
	echo("request completed; TIME:".round($time, 2)." seconds, memory: ".getMemory().LR);
	echo buildTables($allData, $foil, $compareType, $availChangeMin, $availChangeMax, $minPrice, $maxPrice, $plusminus, $stackDisplay, $skipUnchanged);
}

function setChangeValue(&$card, $forFoil){

	if (!$forFoil){
		$props = array("baseAvail", "basePrice");
	} else $props = array("foilAvail", "foilPrice");

	$card["baseAvailChange"] = array(0, 0);
	$card["basePriceChange"] = array(0, 0);
	$card["foilAvailChange"] = array(0, 0);
	$card["foilPriceChange"] = array(0, 0);

	$absStockChange = $card["points"][sizeof($card["points"])-1][$props[0]] - $card["points"][0][$props[0]];
	$absPriceChange = $card["points"][sizeof($card["points"])-1][$props[1]] - $card["points"][0][$props[1]];


	//msg($card["cardname"]); msg($absPriceChange); msg($card["points"][0][$props[1]]);
	$pctStockChange = $card["points"][0][$props[0]] != 0 ? round($absStockChange / $card["points"][0][$props[0]] * 100, 2) : 0;
	$pctPriceChange = $card["points"][0][$props[1]] != 0 ? round($absPriceChange / $card["points"][0][$props[1]] * 100, 2) : 0;

	$card[$props[0]] = array($card["points"][sizeof($card["points"])-1][$props[0]], $card["points"][0][$props[0]]);
	$card[$props[1]] = array($card["points"][sizeof($card["points"])-1][$props[1]], $card["points"][1][$props[1]]);

	$card[$props[0]."Change"] = array($absStockChange, $pctStockChange);
	$card[$props[1]."Change"] = array($absPriceChange, $pctPriceChange);

	//var_export($card["basePrice"]); die();
	return;
}


function buildTables($allSets, $foil, $compareType, $availChangeMin, $availChangeMax, $minPrice, $maxPrice, $plusminus, $stackDisplay, $skipUnchanged){

	$avail = "";
	$change = "";
	$price = "";
	$allHTML = "";

	$index;

	if ($foil == "Non Foil"){
		$avail = "baseAvail";
		$price = "basePrice";
		$volChange = "baseAvailChange";
		$moneyChange = "basePriceChange";
	}
	else {
		$avail = "foilAvail";
		$price = "foilPrice";
		$volChange = "foilAvailChange";
		$moneyChange = "foilPriceChange";
	}

	if ($compareType == "ABS"){
		$index = 0;
	} else $index = 1;

	$colSpan = 9;
	if ($stackDisplay){$colSpan = 10;}
	

	for ($i = 0; $i < sizeof($allSets); $i++){
		$subHTML = "";

		$subHTML .="<table class='moveTable' style='width: 100%'>";

		$subHTML .="<thead>";
		$subHTML .="<tr class='ddisabled'><th class='set' colSpan=".$colSpan.">";
		$subHTML .="<span>".$allSets[$i]["setname"]."</span>";
		$subHTML .="<span> - </span>";
		$subHTML .="<span class='setName'>".$allSets[$i]["setcode"]."</span>";
		$subHTML .="</th></tr>";
		$subHTML .="<tr class='sort'>";
		$subHTML .="<th style='width: 300px' colSpan=1>".$allSets[$i]['setname']." - ".$allSets[$i]['setcode']."</th>";

	//	$subHTML .="<th style='width: 50px'></th>"; // chart link
	//	$subHTML .="<th style='width: 50px'></th>"; // mkm link

		$subHTML .="<th colSpan=1></th>"; // chartpreview
		$subHTML .="<th>PCT</th>";
		$subHTML .="<th>ABS</th>";
		
		if ($stackDisplay){
			$subHTML .="<th>Stack</th>";			
		}

		$subHTML .="<th>R</th>";

		$subHTML .="<th>".$allSets[$i]["compareDate"]."</th>";
		$subHTML .="<th>".$allSets[$i]["lastDate"]."</th>";
	
		/*
		$subHTML .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["compareDate"]."</th>";
		$subHTML .="<th style='width: 100px'>Value (EUR)</br>".$allSets[$i]["lastDate"]."</th>";
		$subHTML .="<th style='width: 70px'>ABS</th>";
		$subHTML .="<th style='width: 70px'>PCT</th>";
		$subHTML .="</tr></thead>";
		*/
		
		
		$subHTML .="<tbody>";

		$realEntries = 0;

		for ($j = 0; $j < sizeof($allSets[$i]["shakers"]); $j++){
			$card = $allSets[$i]["shakers"][$j];

			//unset($card["points"]);
			//echo $price.LR;
			//foreach ($card as $key => $value){
			//	echo $key." => ".$value.LR;
			//}
			//var_export($card);
			//die();
			$relChange = $card[$volChange][$index];
			//echo $relChange; die();
			
			if ($card[$volChange][0] > 0){$class = "green";} else $class = "red";

			if ($skipUnchanged && $card[$volChange][$index] == 0){continue;}
			if ($minPrice && $card[$price][0] <= $minPrice){continue;}
			if ($maxPrice && $card[$price][0] >= $maxPrice){continue;}
			
			if ($plusminus){
				if (abs($card[$volChange][$index]) < abs($availChangeMin)){continue;}
			}
			else if ($availChangeMin == 0 && $availChangeMax == 0){}
			else if ($availChangeMin != 0 && $availChangeMax != 0){
				if ($availChangeMin < 0 && $availChangeMax < 0){
					if ($relChange > $availChangeMin || $relChange < $availChangeMax){continue;}
				}
				else if ($availChangeMin > 0 && $availChangeMax > 0){
					if ($relChange < $availChangeMin || $relChange > $availChangeMax){continue;}
				}
				else {
					if ($relChange < $availChangeMin || $relChange > $availChangeMax){continue;}
				}
			}
			/*	echo $availChangeMin."</br>";
				echo $availChangeMax."</br>";
				echo $relChange."</br>";
				die();
			*/
			else {
				if ($availChangeMin <= 0 && $relChange > $availChangeMin){continue;}
				if ($availChangeMin > 0 && $relChange < $availChangeMin){continue;}
				if ($availChangeMax < 0 && $relChange < $availChangeMax){continue;}
				if ($availChangeMax > 0 && $relChange > $availChangeMax){continue;}
			}	

			$realEntries++;

			$cardUrl = "https://deckbox.org/mtg/".rawurlencode($card["cardname"]);
			$cardname = rawurlencode($card["cardname"]);

			$subHTML .="<tr>";

			$subHTML .="<td class='cardEntryContainer'>";
			//$subHTML .= "<input type='button' value='Fav+' onclick='charter.addSingleFavorite($(this))'>";
			$subHTML .= "<div class='addFavWrapper'><div onclick='charter.addSingleFavorite($(this))'>fav+</div></div>";

			$subHTML .= "<div class='hover' data-hover='$cardname'>";
				$chartUrl = "charts.php?type=preset&set=".urlencode($allSets[$i]["setname"])."&card=".urlencode($card["cardname"]);
			$subHTML .= "<div><a target='_blank' href=".$chartUrl.">".$card['cardname']."</a></div>";
			$subHTML .= "</div>";

			$subHTML .="<div><a target='_blank' href=".getMKMURL($allSets[$i]["setname"], $card["cardname"]).">MKM</a><div>";
			$subHTML .="</td>";


			$subHTML .="<td class='smallChart'></td>";
			$subHTML .="<td class='".$class."'>".$card[$volChange][1]." %</td>";
			$subHTML .="<td class='".$class."'>".$card[$volChange][0]."</td>";
			$subHTML .="<td>".substr($card["rarity"], 0, 1)."</td>";

			$subHTML .="<td>".$card[$avail][sizeof($card[$avail])-1]."</td>";
			$subHTML .="<td>".$card[$avail][0]."</td>";		
		}

		$subHTML .="</tbody></table>";
		if ($realEntries){
			$allHTML .= $subHTML;
		}

	}
	return $allHTML;
}

function writeAndClose($db, $setcode, $date, $pulldata){
	//echo "Writing ".$setcode.", entries: ".sizeof($pulldata).", date ".$date."\n";
	$GLOBALS["cards"] += sizeof($pulldata);

	$db->connection->beginTransaction();

	$success = false;

	if ($db->insertSingleSetPull($setcode, $date, $pulldata)){
		if ($db->closeSetEntry($setcode, $date)){
			$success = true;
		}
	}

	if (!$success){
		$db->connection->rollback();
		msg("error, rolling back");
		return false;
	}
	$db->connection->commit();
	return true;
}

function getContext(){
	return $context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T"
				)
			)
	);
}


function msg($print){

	echo $print.LR;
	return;
	$lr = "\n";
	if (php_sapi_name() != 'cli'){$lr = "</br>";}
	print_r($lr.$print);
	//echo $print."</br>";
}



function crawlBaseSet($db, $context, $pull){

	$set = array();
	$exit = 0;
	$page = 0;
	$maxPages = 0;
	$prop = "data-original-title";

	while(!$exit){
		$page++;
		$url = "https://www.cardmarket.com/en/Magic/Products/Singles/" . doReplace($pull["setname"])."?onlyAvailable=on&sortBy=locName_asc&perSite=50";
		$url .= "&site=".$page;
		//msg($url);

		$html = file_get_html($url, false, $context);// $GLOBALS["requests"]++;

		if (!$html){
			msg("NO HTML ! ".$pull["setcode"]);
			sleep(5);
			$html = file_get_html($url, false, $context);

			if (!$html){msg("still not!"); die();}
		}

		if (!$maxPages){
			$dropdown = $html->find("div.dropup > div.dropdown-menu", 0);
			$maxPages = $dropdown ? sizeof($dropdown->children()) : 1;
			//msg("pages ".$maxPages); $page = $maxPages -1; continue;
		}

		$rows = $html->find(".table-body", 0)->children();

		for ($k = 0; $k < sizeof($rows); $k++){
			$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->plaintext;
			//msg("pulling row $k with name $name ");
			$baseAvail = 0;
			$basePrice = 0.00;
			$foilAvail = 0;
			$foilPrice = 0.00;
			$rarity = "";
			
			if ($pull["foil"] && !$pull["nonfoil"]){
				$foilAvail = $rows[$k]->children(4)->children(0)->plaintext;
				$foilPrice = $rows[$k]->children(5)->plaintext;
				$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));
			}
			else {
				if ($pull["nonfoil"]){
					$baseAvail = $rows[$k]->children(4)->children(0)->plaintext;
					$basePrice = $rows[$k]->children(5)->plaintext;
					$basePrice = str_replace(",", ".", substr($basePrice, 0, strlen($basePrice)-9));
				}
				if ($pull["foil"]){
					$foilAvail = $rows[$k]->children(6)->plaintext;
					$foilPrice = $rows[$k]->children(7)->plaintext;
					$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strlen($foilPrice)-9));
				}
			}

			$rarity = substr($rows[$k]->children(3)->find(".icon", 0)->{$prop}, 0, 1);
			doAdd($name, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, $set);
		}

		if ($page >= $maxPages){
			break;
		}
		else if ($page >= 15){
			echo "ERROR \n\n";
			$GLOBALS["errors"][] = $pull["setcode"];
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}

function crawlGameBoxes($db, $context, $pull){
	$game = substr($pull["setname"], 0, strlen($pull["setname"])-6);
	$set = array();	

	for ($j = 1; $j < 10; $j++){			
		$url = "https://www.cardmarket.com/en/".$game."/Products/Booster-Boxes?sortBy=name_asc&perSite=50";
		$url .= "&site=".$j;

		$html = file_get_html($url, false, $context);// $GLOBALS["requests"]++;
		$rows = $html->find(".table-body", 0)->children();

		for ($k = 0; $k < sizeof($rows); $k++){
			$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->innertext;
			$baseAvail = $rows[$k]->children(4)->children(0)->innertext;
			$basePrice = 0.00;
			if ($baseAvail){
				$basePrice = $rows[$k]->children(5)->innertext;
				$basePrice = str_replace(",", ".", $basePrice);
				$basePrice = substr($basePrice, 0, strlen($basePrice)-9);
			} 

			doAdd($name, "S", $baseAvail, $basePrice, intval(0), floatval(0), $set);
		}
		
		if (sizeof($rows) < 50){
			msg("last page");
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}

function crawlFreeURL($db, $context, $pull){
	
	$set = array();	
	$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Sets?searchString=Sealed&sortBy=sellVolume_desc&perSite=50";
	
	for ($j = 0; $j < 10; $j++){			
		$url = $baseUrl."&site=".$j;
		$html = file_get_html($url, false, $context);// $GLOBALS["requests"]++;
		$rows = $html->find(".table-body", 0)->children();
		for ($k = 0; $k < sizeof($rows); $k++){
			$name = $rows[$k]->children(3)->children(0)->children(0)->children(0)->innertext;
			$baseAvail = $rows[$k]->children(4)->children(0)->innertext;
			$basePrice = 0.00;
			if ($baseAvail){
				$basePrice = $rows[$k]->children(5)->innertext;
				$basePrice = str_replace(",", ".", $basePrice);
				$basePrice = substr($basePrice, 0, strlen($basePrice)-9);
			}
			doAdd($name, "S", $baseAvail, $basePrice, intval(0), floatval(0), $set);
		}
		
		if (sizeof($rows) < 50){
			echo "last page - ";
			break;
		}
	}

	$html->clear();
	unset($html);
	return $set;
}

function doAdd($cardname, $rarity, $baseAvail, $basePrice, $foilAvail, $foilPrice, &$set){
	$set[] = array(
		"cardname" => $cardname,
		"rarity" => $rarity,
		"baseAvail" => intval($baseAvail),
		"basePrice" => floatval($basePrice),
		"foilAvail" => intval($foilAvail),
		"foilPrice" => floatval($foilPrice)
	);
}

function logErrors(){
	echo "\n\n";

	if (!sizeof($GLOBALS["errors"])){echo "NO ERRORS !";}
	else {
		for ($i = 0; $i < sizeof($GLOBALS["errors"]); $i++){
			echo "error: ".$GLOBALS["errors"][$i]."\n";
		}
	}
}

?>
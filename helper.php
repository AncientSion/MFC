<?php


include_once(__DIR__."\global.php");

//phpinfo(); return;

$time = time();
$date = date('d.m.Y', $time);
$time = -microtime(true);

handleNewSetCreation(); return;
checkAllForNull(); return;
updateDate();
checkAllForNull();

$time += microtime(true);




function handleNewSetCreation(){
	$db = DB::app();
	$context = getContext();

	$setcode = "M20";

	echo("handleNewSetCreation: ".$setcode.LR);


	$sql = ("SELECT * FROM 1sets WHERE setcode = '".$setcode."'");
	if ($db->connection->query($sql)->rowCount()){echo "---------invalid setcode".LR; return;}

	$set = array(
		"id" => 0,
		"setcode" => $setcode,
		"setname" => "Core 2020",
		"foil" => 1,
		"nonfoil" => 1,
		"lastPull" => "0000-00-00",
		"type" => 0
	);

	$db->connection->beginTransaction();

	$set["id"] = $db->insertNewSet($set);
	if (!$set["id"]){msg("no set id!"); return;}
	$db->connection->exec("CREATE TABLE ".$set["setcode"]." LIKE 2ed");

	$pulled;

	msg("\n__NOW - ".$set["setname"]." / ".$set["setcode"].", id ".$set["id"].", foil ".$set["foil"].", nonfoil ".$set["nonfoil"]);

	switch ($set["type"]){
		case 0: $pulled = crawlBaseSet($db, $context, $set); break;
		case 1: $pulled = crawlGameBoxes($db, $context, $set); break;
		case 2: $pulled = crawlFreeURL($db, $context, $set); break;
	}

	if (!$pulled){msg("no data pulled!"); return;}

	$cardsInserted = $db->insertNewCardsWithSetID($set["id"], $set["setcode"], $pulled);
	if (!$cardsInserted){msg("didnt insert new cards!"); return;}


	$success = false;

/*	if ($db->insertSingleSetPull($set["setcode"], $date, $pulled)){
		if ($db->closeSetEntry($set["setcode"], $date)){
			$success = true;
		}
	}
*/
	if ($cardsInserted){
		msg("success!");
		$db->connection->commit();
	}
	else {
		msg("rollback !");
		$db->connection->rollback();
	}
}

function checkSingleSetForNull($db, $setcode){

	$query = "SELECT * FROM ".$setcode." WHERE cardid IS NULL";
	$stmt = $db->connection->prepare($query);
	$stmt->execute();

	$missings = array();
	$subResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($subResults as $error){
		$missings[] = $setcode." - ".$error["cardname"];
	}
	return $missings;
}

function checkAllForNull(){

	$db = DB::app();
	$stmt = $db->connection->prepare("SHOW TABLES");
	$stmt->execute();

	$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
	//var_export($results); return;
	$missings = array();

	foreach ($tables as $table){
		if ($db->isNoSetTable($table['Tables_in_crawl'])){continue;}
		//msg("checking ".$table['Tables_in_crawl']);

		$query = "SELECT * FROM ".$table['Tables_in_crawl']." WHERE cardid IS NULL";
		$stmt = $db->connection->prepare($query);
		$stmt->execute();

		$subResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($subResults)){
			echo "error in ".$table['Tables_in_crawl'].": ".sizeof($subResults).LR;
			foreach ($subResults as $entry){
				print_r($entry); break;
			}
		}
	}
	msg("DONE");
}

function updateDate(){
	$db = DB::app();
	$stmt = $db->connection->prepare("SHOW TABLES");
	$stmt->execute();

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($results as $result){
		if ($db->isNoSetTable($result['Tables_in_crawl'])){continue;}

		$query = "UPDATE ".$result['Tables_in_crawl']." SET date = '2019-05-25' WHERE date = '2019-05-26'";
		$stmt = $db->connection->prepare($query);
		$stmt->execute();
		if ($stmt->errorCode() == 0){
			echo "updated ".$result['Tables_in_crawl']."\n";
		}
	}
}

function deleteNull(){
	$db = DB::app();
	$stmt = $db->connection->prepare("SHOW TABLES");
	$stmt->execute();

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($results as $result){
		if ($db->isNoSetTable($table['Tables_in_crawl'])){continue;}

		$query = "DELETE FROM ".$result['Tables_in_crawl']." WHERE cardid IS NULL";
		$stmt = $db->connection->prepare($query);
		$stmt->execute();
		if ($stmt->errorCode() == 0){
			echo "deleted from ".$result['Tables_in_crawl']."\n";
		}
	}
}

function reDoSetsTable(){
	$db = DB::app();
	$stmt = $db->connection->prepare("DROP TABLE IF EXISTS 1sets");
	$stmt->execute();

	$sql = "CREATE TABLE 1sets (id int(3) primary key AUTO_INCREMENT, setcode varchar(4) default '' not null, setname varchar(255) default '' not null, foil tinyint(1) default 1 not null, nonfoil tinyint(1) default 0 not null)";

	$stmt = $db->connection->prepare($sql);
	$stmt->execute();
}

function handleSetCreationFromJSON(){
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);
	$files = array_slice($files, 2);

	$db = DB::app();

	$stmt = $db->connection->prepare("SHOW TABLES");
	$stmt->execute();
	$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($files as $file){
		if ($file == "cardlist.json" || $file == "avail.json" || $file == "EDH.json"){continue;}
		$insert = true;
		$setcode = substr($file, 0, strpos($file, "."));

		foreach ($tables as $table){
			if (strtolower($setcode) == $table['Tables_in_crawl']){
				msg("skipping ".$file); 
				$insert = false;
				break;
			}
		}
	
		if (!$insert){continue;}

		msg("doing ".$file);

		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);

		recreateSubTable($db, $setcode);
		insertSetIntoSets($db, $data, $setcode);
		insertCardsIntoCardsTable($db, $data, $setcode);
		JSONTOSQL($db, $data, $setcode);
		$missings = checkSingleSetForNull($db, $setcode);

		if (sizeof($missings)){
			msg(implode($missings, " --"));
			msg("\n early return!");
			return;
		} else msg("all fine");
	}
}

function insertSetIntoSets($db, $data, $setcode){
	msg("insertSetIntoSets for set ".$setcode);

	$stmt = $db->connection->prepare(
			"INSERT INTO 1sets 
				(id, setcode, setname, foil, nonfoil)
			VALUES
				(id, :setcode, :setname, :foil, :nonfoil)
	");
	$foil = 1;
	$nonfoil = 1;

	$stmt->bindParam(":setcode", $setcode);
	$stmt->bindParam(":setname", $data->content[0]->set);
	$stmt->bindParam(":foil", $foil);
	$stmt->bindParam(":nonfoil", $nonfoil);
	$stmt->execute();
}

function insertCardsIntoCardsTable($db, $json, $setcode){
	msg("insertCardsIntoCardsTable");

	$entries = 0;

	$cards = $json->content[sizeof($json->content)-1]->data;

	$stmt = DB::app()->connection->prepare(
		"INSERT INTO 1cards 
			(id, setid, cardname, setcode, rarity)
		VALUES
			(0, (SELECT id from 1sets WHERE setcode = :setcodeA), :cardname, :setcodeB, :rarity)
	");

	$stmt->bindParam(":setcodeA", $json->code);
	$stmt->bindParam(":setcodeB", $json->code);

	//$inserts = array();
	foreach ($cards as $card){
		$entries++;
		$stmt->bindParam(":cardname", $card->name);
		$stmt->bindParam(":rarity", $card->rarity);
		$stmt->execute();
		//$inserts[] = $card->name;
	};

	msg("inserted from file ".$setcode." to allCards, ".$entries." entries");
	//implode($inserts, ", ");
}

function recreateSubTable($db, $setcode){
	msg("recreateSubTable");

	$sql = "DROP TABLE IF EXISTS ".$setcode;
	//msg($sql);
	$db->connection->query($sql);

	$sql = "create table ".$setcode." (id int(5) primary key AUTO_INCREMENT, cardid int(5) default 0, cardname varchar(100) default '' not null, baseAvail int(5) default 0 not null, basePrice decimal(5, 2) default 0 not null, foilAvail int(5) default 0 not null, foilPrice decimal(5, 2) default 0 not null, date date not null)";

	$db->connection->query($sql);
	msg("CREATE TABLE ".$setcode);
}



function JSONTOSQL($db, $json, $setcode){
	msg("new JSONTSQL");

	$stmt = $db->connection->prepare(
		"SELECT id FROM 1cards WHERE setcode = :setcode AND cardname = :cardname
	");

	$stmt->bindParam(":setcode", $setcode);

	$ids = array();

	//var_export($stmt);

	foreach ($json->content[sizeof($json->content)-1]->data as $card){
		$stmt->bindParam(":cardname", $card->name);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($result){
			$ids[] = array($card->name, $result["id"]);
		}
	}

	for ($i = 0; $i < sizeof($json->content); $i++){
		for ($j = 0; $j < sizeof($json->content[$i]->data); $j++){
			for ($k = 0; $k < sizeof($ids); $k++){
				if ($json->content[$i]->data[$j]->name == $ids[$k][0]){
					$json->content[$i]->data[$j]->cardid = $ids[$k][1];
					break;	
				}
			}
		}
	}

	$errors = array();
	foreach ($json->content as $day){
		foreach ($day->data as $card){
			if (!(isset($card->cardid))){
				$errors[] = $day->date.": ".$card->name;
			}
		}
	}

	if (sizeof($errors)){
		msg(implode($errors, " -- "));
		//die();
	}

	$stmt = $db->connection->prepare(
		"INSERT INTO ".$setcode." 
			(id, cardid, cardname, baseAvail, basePrice, foilAvail, foilPrice, date)
		VALUES
			(0, :cardid, :cardname, :baseAvail, :basePrice, :foilAvail, :foilPrice, :date)
	");
	//$stmt->bindParam(":setcode", $setcode);

	msg("all cards id'ed, filling table ".$setcode);

	foreach ($json->content as $day){

		$stmt->bindValue(":date", date("Y-m-d", strtotime(str_replace(".", "-", $day->date))));

		foreach ($day->data as $card){
			//var_export($card); die();
			$stmt->bindParam(":cardid", $card->cardid);
			$stmt->bindParam(":cardname", $card->name);
			$stmt->bindParam(":baseAvail", $card->baseAvail);
			$stmt->bindParam(":basePrice", $card->basePrice);
			$stmt->bindParam(":foilAvail", $card->foilAvail);
			$stmt->bindParam(":foilPrice", $card->foilPrice);

			$stmt->execute();
		}
	}

	msg("done, inserted: ".(sizeof($json->content) * sizeof($json->content[sizeof($json->content)-1]->data))." rows");
	//die();
}

function xJSONTOSQL($db, $json, $setcode){
	msg("old JSONTSQL");

	$stmt = $db->connection->prepare(
		"INSERT INTO ".$setcode." 
			(id, cardid, cardname, baseAvail, basePrice, foilAvail, foilPrice, date)
		VALUES
			(0, (SELECT id from 1cards WHERE cards.setcode = :setcode AND cards.cardname = :cardnameA), :cardnameB, :baseAvail, :basePrice, :foilAvail, :foilPrice, :date)
	");
	$stmt->bindParam(":setcode", $setcode);

	msg("filling table ".$setcode);

	foreach ($json->content as $day){

		$stmt->bindValue(":date", date("Y-m-d", strtotime(str_replace(".", "-", $day->date))));

		foreach ($day->data as $card){
			$stmt->bindParam(":cardnameA", $card->name);
			$stmt->bindParam(":cardnameB", $card->name);
			$stmt->bindParam(":baseAvail", $card->baseAvail);
			$stmt->bindParam(":basePrice", $card->basePrice);
			$stmt->bindParam(":foilAvail", $card->foilAvail);
			$stmt->bindParam(":foilPrice", $card->foilPrice);

			$stmt->execute();
		}
	}

	msg("done, inserted: ".(sizeof($json->content) * sizeof($json->content[sizeof($json->content)-1]->data))." rows");
	return;
}

function recreateAllCardsTable(){
	msg("recreateAllCardsTable");
	$db = DB::app();
	$sql = "DROP TABLE IF EXISTS 1cards";
	DB::app()->connection->query($sql);

	$sql = "CREATE TABLE 1cards (id int(5) primary key AUTO_INCREMENT, setid int(3) default 0 not null, cardname varchar(100) default '' not null, setcode varchar(4) default '' not null, rarity varchar(1) default '' not null)";
	DB::app()->connection->query($sql);
}


function deleteDoubledCardEntries(){
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);

	$files = array_slice($files, 2);

	foreach ($files as $file){
		echo "doing file ".$file."\n";
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);

		$doubles = array();

		for ($i = sizeof($data->cards)-1; $i >= 0; $i--){
			echo $data->cards[$i]->name."\n";
			for ($j = $i-1; $j >= 0; $j--){
				if ($data->cards[$i]->name == $data->cards[$j]->name){
					//$doubles[] = $data->cards[$i]->name;
					echo "double found at index ".$i." / ".$j." --- ".$data->cards[$i]->name."\n";
					array_splice($data->cards, $i, 1);
					break;
				}
			}
		}

		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($data));
		fclose($handle);
	}

}

function convertToBase(){
	
	$codes = array("EXP", "MPS", "AIN", "DCI", "FNM", "BABP", "GDP", "JR", "CPR", "ALP", "UBT", "GME");
	//$codes = array("GDP");
	
	$folder = '../htdocs/crawl/output';
	
	for ($i = 0; $i < sizeof($codes); $i++){
		$newJson;
		
		$json = file_get_contents($folder."/".$codes[$i].".json");
		$json = json_decode($json);		

		for ($j = 0; $j < sizeof($json->content)-1; $j++){
			for ($k = 0; $k < sizeof($json->content[$j]->data); $k++){
			/*	$json->content[$j]->data[$k]->baseAvail = $json->content[$j]->data[$k]->foilAvail;
				$json->content[$j]->data[$k]->basePrice = $json->content[$j]->data[$k]->foilPrice;
				$json->content[$j]->data[$k]->foilAvail = 0;
				$json->content[$j]->data[$k]->foilPrice = 0;
			*/
			
				if ($json->content[$j]->data[$k]->baseAvail == 0){
				/*	echo "ding \n";
					var_export($json->content[$j]->date);
					echo "\n";
					echo "\n";
					var_export($json->content[$j]->data[$k]);
					echo "\n";
					echo "\n";
					var_export($json->content[$j-1]->data[$k]);
					echo "\n";
					echo "\n";
				*/
				//	return;
					
					$json->content[$j]->data[$k]->baseAvail = $json->content[$j-1]->data[$k]->baseAvail;
					$json->content[$j]->data[$k]->basePrice = $json->content[$j-1]->data[$k]->basePrice;
				}
		
			}
		}
		
		
		$handle = fopen(($folder."/".$codes[$i].".json"), "w+");
		fwrite($handle, json_encode($json));
		fclose($handle);

	}
}
	
	

function checkValidJson(){
	$file = null;
	$folder = '../htdocs/crawl/output';
	$files = scandir($folder);

	$files = array_slice($files, 2);
	
	foreach ($files as $file){
		//echo "doing file ".$file."\n";
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);
		
		if (!$data){
			echo "invalid JSON on " . $file;
		}// else echo "valid! \n";
	}
}	
	

function getLengthOfSet($set){
	
	$data = json_decode(file_get_contents("../htdocs/crawl/output/".$set.".json"));
	
	//echo sizeof($data->content);
	foreach ($data->content as $day){
		echo $day->date."\n";
	}
	
}
	

function deleteForeignFromInput(){
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);

	$files = array_slice($files, 2);

	foreach ($files as $file){
		echo "doing file ".$file."\n";
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);

		for ($i = 0; $i < sizeof($data->cards); $i++){
			$data->cards[$i]->foreignData = array();
		}

		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($data));
		fclose($handle);
	}
}

function deleteFromWithin($amountToDelete){
	//return;
	
	echo "start\n";
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);

	$files = array_slice($files, 2);
	//echo "files: ".sizeof($files)."\n\n\n";

	foreach ($files as $file){
		if ($file != "_DGB.json"){continue;}
		
		echo $file."\n\n";
		$json = file_get_contents($folder."/".$file);
		$json = json_decode($json);


		for ($i = sizeof($json->content)-1; $i >= 0 ; $i--){
			for ($j = 0; $j < sizeof($json->content[$i]->data); $j++){
				if ($json->content[$i]->data[$j]->name == "Cross Worlds Booster Box"){
					//msg($data->content[$i]->date . " index: ".$i);
					array_splice($json->content, $i, 1);
					break;
				}
			}
		}
		//die();

		for ($i = sizeof($json->content)-1; $i >= 0 ; $i--){
			for ($j = 0; $j < sizeof($json->content[$i]->data); $j++){
				if ($json->content[$i]->data[$j]->name ==  "Cross Worlds Booster Box"){
					msg($json->content[$i]->date . " index: ".$i);
					die();
				}
			}
		}
		//die();
		
		//array_splice($data->content, $amountToDelete);
		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($json));
		fclose($handle);
	}
}

function deleteFromEnd($amountToDelete){
	//return;
	
	echo "start\n";
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);

	$files = array_slice($files, 2);
	echo "files: ".sizeof($files)."\n\n\n";

	foreach ($files as $file){
		
		echo $file."\n\n";
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);
		
		array_splice($data->content, $amountToDelete);
		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($data));
		fclose($handle);
	}
}

function deleteFromFront($amountToDelete){
	echo "start\n";
	$file = null;
	$folder = '../htdocs/crawl/fix';
	$files = scandir($folder);

	$files = array_slice($files, 2);
	echo "files: ".sizeof($files)."\n\n\n";

	foreach ($files as $file){
		echo $file."\n\n";
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);
		
		$new = array();
		
		for ($i = 0 + $amountToDelete; $i < sizeof($data->content); $i++){
			$new[] = $data->content[$i];
		}
		
		$data->content = $new;
		
		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($data));
		fclose($handle);
	}
}

function slice(){

	$time = time();
	$date = date('d.m.Y', $time);

	echo "start\n";
	$file = null;
	$folder = '../htdocs/crawl/output';
	$files = scandir($folder);

	$files = array_slice($files, 2);
	echo "files: ".sizeof($files)."\n\n\n";

	foreach ($files as $file){
		if ($file == "avail.json"){continue;}
		if ($file == "cardlist.json"){continue;}

		if ($file != "C18.json"){continue;}
		echo $file."\n\n";

		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);

		$content = array($data->content[0]);

		for ($i = 1; $i < sizeof($data->content); $i++){

			$lastTimeStamp = date_create_from_format("!d.m.Y", $content[sizeof($content)-1]->date);
		//var_export($lastTimeStamp);
			$nowTimeStamp = date_create_from_format("!d.m.Y", $data->content[$i]->date);
		//var_export($nowTimeStamp);
			$diff = date_diff($nowTimeStamp, $lastTimeStamp)->format("%d")."\n";
		//echo $diff."\n";
			if ($diff == 1){
				//echo "skip \n";
				continue;}
			//echo "add \n";
			$content[] = $data->content[$i];
		}

		return;
		$new = array("code" => $data->code, "content" => $content);
		$handle = fopen($folder."/".$file, "w+");
		fwrite($handle, json_encode($new));
		fclose($handle);

	}
}


function writedAndClose($code, $data){
	echo "Writing ".$code.", entries: ".sizeof($data["data"])."\n\n";
	$GLOBALS["cards"] += sizeof($data["data"]);
	//$file = fopen(__DIR__."/output/" . $code .".json", "a");
	$file = fopen(__DIR__."/output/" . $code .".json", "r+");
	fseek($file, -2, SEEK_END);
	fwrite($file, ",".json_encode($data)."\n"."]}");
	fclose($file);
}


function alterShipFiles(){

	$files = array_slice(scandir("server/ships"), 2);

	foreach ($files as $file){
		$content = file("server/ships/".$file);
		$new = array();

		foreach ($content as $line){
			$entry = substr(trim($line), 8, 3);
			if ($entry != "pro"){
				$new[] = $line;
			}
			else {
				$replace = true;
				$new[] = "\t".'public $profile = array(0.9, 1.1);'."\n";
			}
		}

		if ($replace){
			$dest = fopen("server/ships/".$file, "w");
			fwrite($dest, implode($new));
			fclose($dest);
		}
	}
}

?>
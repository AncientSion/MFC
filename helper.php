<?php


include_once(__DIR__."\global.php");


//convertToBase();
//deleteDoubledCardEntries();
//checkValidJson();
//deleteForeignFromInput();

//deleteFromFront(3);
//fillAllCards(); return;
JSONTOSQL(); return;
//create table ORI (id int(5) primary key AUTO_INCREMENT, cardname varchar(50) default 0 not null, rarity varchar(1) default "" not null, baseAvail int(5) default 0 not null, basePrice decimal(5, 3) default 0 not null, FoilAvail int(5) default 0 not null, FoilPrice decimal(5, 3) default 0 not null, pull date not null);


function fillAllCards(){
	$file = null;
	$folder = '../htdocs/crawl/input';
	$files = scandir($folder);

	$files = array_slice($files, 2);

	$entries = 0;
	
	foreach ($files as $file){
		if ($file == "cardlist.json" || $file == "avail.json"){continue;}
		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);
		$cards = $data->cards;

		$stmt = DB::app()->connection->prepare(
			"INSERT INTO cards 
				(id, cardname, setcode, rarity)
			VALUES
				(0, :cardname, :setcode, :rarity)
		");

		$stmt->bindParam(":setcode", $data->code);
		foreach ($cards as $card){
			$entries++;
			$stmt->bindParam(":cardname", $card->name);
			$stmt->bindParam(":rarity", $card->rarity);
			$stmt->execute();
		};

		echo "ready, insert: ".$entries."\n";
		//return;
	}
}


function JSONTOSQL(){
	$file = null;
	$folder = '../htdocs/crawl/output';
	$files = scandir($folder);
	$files = array_slice($files, 2);

	$sets = 0;
	$entries = 0;
	foreach ($files as $file){
		if ($file == "cardlist.json" || $file == "avail.json"){continue;}
		echo "doing ".substr($file, 0, 3)."\n";
		$sets++;
		if ($sets >= 2){return;}

		$setcode = substr($file, 0, 3);
		$sql = "create table ".$setcode." (id int(5) primary key AUTO_INCREMENT, cardid int(5) default 0, baseAvail int(5) default 0 not null, basePrice decimal(5, 3) default 0 not null, foilAvail int(5) default 0 not null, foilPrice decimal(5, 3) default 0 not null, date date not null)";

		//echo $sql;// return;

		DB::app()->connection->query($sql);




		$stmt = DB::app()->connection->prepare(
			"INSERT INTO ".$setcode." 
				(id, cardid, baseAvail, basePrice, foilAvail, foilPrice, date)
			VALUES
				(0, (SELECT id from cards WHERE cards.setcode = :setcode AND cards.cardname = :cardname), :baseAvail, :basePrice, :foilAvail, :foilPrice, :date)
		");
		$stmt->bindParam(":setcode", $setcode);

		$data = file_get_contents($folder."/".$file);
		$data = json_decode($data);

		foreach ($data->content as $day){

			$stmt->bindParam(":date", date("Y-m-d", strtotime(str_replace(".", "-", $day->date))));

			foreach ($day->data as $entry){
				//echo $entry->name."\n";
				$entries++;
				//$stmt->bindParam(":cardid", $entry->name);
				echo $entry->name."\n";
				$stmt->bindParam(":cardname", $entry->name);
				$stmt->bindParam(":baseAvail", $entry->baseAvail);
				$stmt->bindParam(":basePrice", $entry->basePrice);
				$stmt->bindParam(":foilAvail", $entry->foilAvail);
				$stmt->bindParam(":foilPrice", $entry->foilPrice);

				//echo $entry->name." ".$entry->foilPrice."\n";

				$stmt->execute();
			}
		}
	}

	echo "ready, insert: ".$entries."\n";
	return;
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
<?php


include_once(__DIR__."\global.php");


deleteFromFront(4);

function deleteFromEnd($amountToDelete){
	return;
	
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
	return;
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
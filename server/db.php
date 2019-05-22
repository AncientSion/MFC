<?php

	class DB {

		public $connection = null;
		static protected $instance = null;

		function __construct(){
			if ($this->connection === null){
				$access = array("root", 147147);
				$this->connection = new PDO("mysql:host=localhost;dbname=crawl", $access[0],$access[1]);
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			}
		}
		
		static public function app(){
	        if(self::$instance === null OR !is_a(self::$instance, "DB")) {
	            self::$instance = new DB();
	        }
	        return self::$instance;
		}

		public function getPickedSetNames($setcodes){
			$sql = "SELECT * FROM 1sets WHERE setcode = :setcode";
			$stmt = $this->connection->prepare($sql);

			$names = array();
			
			for ($i = 0; $i < sizeof($setcodes); $i++){
				$stmt->bindParam(":setcode", $setcodes[$i]);
				$stmt->execute();
				$code = $stmt->fetch(PDO::FETCH_ASSOC);
				$names[] = $code;
			}

			return $names;
		}

		public function getAllPickedCardsForShakersFromDB($setcodes, $rarities){
			$sql = "SELECT * FROM 1cards WHERE setcode = :setcode AND (";

			for ($i = 0; $i < sizeof($rarities); $i++){
				$sql .= "rarity = :rarity".($i+1)." ";

				if (sizeof($rarities) > 1 && $i != sizeof($rarities)-1){
					$sql .= " OR ";
				}

				if ($i == sizeof($rarities)-1){
					$sql .= ")";
				}
			}

			$stmt = $this->connection->prepare($sql);

			$sets = array();

			for ($i = 0; $i < sizeof($setcodes); $i++){
				$set = array();
				$stmt->bindParam(":setcode", $setcodes[$i]);

				for ($j = 0; $j < sizeof($rarities); $j++){
					//message("bind rarity".($j+1));
					$stmt->bindParam(":rarity".($j+1), $rarities[$j]);
				}

				//message("executing");
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$set = array_merge($result, $set);

				$sets[] = $set;
			}

			return $sets;
		}

		public function isNoSetTable($string){
			if ($string == "1cards" || $string == "1favs" || $string == "1sets"){
				return true;
			} return false;
		}

		public function getAllSets(){
			$stmt = $this->connection->prepare("SELECT * FROM 1sets");
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		public function getLastPullDate(){
			$sql = "SELECT lastPull from 1sets WHERE id ORDER BY id DESC LIMIT 1";
			foreach ($this->connection->query($sql) as $result){
				return $result["lastPull"];
			}
		}

		public function getAllCards(){

			$tables = $this->getAllSets();
			$data = array();

			$stmt = $this->connection->prepare("
				SELECT * FROM 1cards WHERE setcode = :setcode"
			);
			
			foreach ($tables as $table){
				$set = array("setcode" => $table['setcode'], "setname" => $table["setname"], "cards" => array());
				$stmt->bindParam(":setcode", $table["setcode"]);				
				$stmt->execute();
				$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

				$set["cards"] = $cards;
				$data[] = $set;
			}
			return $data;
		}

		public function getBulkChartData($setcodes, &$cardsets, $delve){

			$limit = ($delve == 0 ? 500 : $delve+1);

			for ($i = 0; $i < sizeof($cardsets); $i++){
				$baseSQL = "SELECT * FROM (SELECT id, baseAvail, basePrice, foilAvail, foilPrice, date FROM $setcodes[$i] WHERE cardid = :cardid ORDER BY id DESC LIMIT ".$limit.")var1 ORDER BY id ASC";

				$stmt = $this->connection->prepare($baseSQL);

				for ($j = 0; $j < sizeof($cardsets[$i]); $j++){
					$stmt->bindParam(":cardid", $cardsets[$i][$j]["id"]);

					$stmt->execute();
					$points = $stmt->fetchAll(PDO::FETCH_ASSOC);

					$cardsets[$i][$j]["points"] = $points;
					//return;
				}
			}

			//return $cardsets;
		}

		public function getChartData($setcode, $cardname){
			//debug("request ".$cardname);
			$sql = 'SELECT * FROM '.$setcode.' WHERE cardid = (SELECT id FROM 1cards WHERE cardname = "'.$cardname.'" AND setcode = "'.$setcode.'")';
			$stmt = $this->connection->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			//debug("fullfill ".$cardname);
			return $result;
		}

		public function insertSingleSetPull($setcode, $time, $data){
			//return true;
			$stmt = $this->connection->prepare(
				"INSERT INTO ".$setcode." 
					(id, cardid, cardname, baseAvail, basePrice, foilAvail, foilPrice, date)
				VALUE 
					(0, (SELECT id FROM 1cards WHERE cardname = :cardnameA AND setcode = :setcode), :cardnameB, :baseAvail, :basePrice, :foilAvail, :foilPrice, :time)
				");

			//echo $time; die();
			//message("-".$time."-");
			$stmt->bindValue(":time", $time);
			$stmt->bindValue(":setcode", $setcode);

			for ($i = 0; $i < sizeof($data); $i++){
				//message($data[$i]["cardname"]);
				$stmt->bindParam(":cardnameA", $data[$i]["cardname"]);
				$stmt->bindParam(":cardnameB", $data[$i]["cardname"]);
				$stmt->bindParam(":baseAvail", $data[$i]["baseAvail"]);
				$stmt->bindParam(":basePrice", $data[$i]["basePrice"]);
				$stmt->bindParam(":foilAvail", $data[$i]["foilAvail"]);
				$stmt->bindParam(":foilPrice", $data[$i]["foilPrice"]);

				$stmt->execute();

				if ($stmt->errorCode() == 0){
					continue;
				} return false;
			}
			return true;
		}


		public function getSetsToPull($date){
			$stmt = $this->connection->prepare("
				SELECT * FROM 1sets WHERE lastPull < '$date' ORDER BY id ASC
			");
		//	$stmt = $this->connection->prepare("SELECT * FROM 1sets where setcode = 'CPR'");
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

			//$this->connection->query("UPDATE 1sets SET open = 0 WHERE id");
			return $result;
		}

		public function closeSetEntry($setcode, $date){			
			$sql = "UPDATE 1sets SET lastPull = '$date' WHERE setcode = '$setcode'";
			//message($sql);
			$stmt = $this->connection->prepare($sql);
			$stmt->execute();
			//message($stmt->rowCount());
			return $stmt->rowCount();
		}

		public function insertFavorites($setCodes, $cardNames, $isFoil){
			$stmt = $this->connection->prepare("
				INSERT into 1favs 
					(id, cardname, setcode, isFoil)
				VALUES(0, :cardname, :setcode, :isFoil)
			");

			for ($i = 0; $i < sizeof($setCodes); $i++){
				$stmt->bindParam(":cardname", $cardNames[$i]);
				$stmt->bindParam(":setcode", $setCodes[$i]);
				$stmt->bindValue(":isFoil", $isFoil[$i] == true ? 1 : 0);
				
				$stmt->execute();
				if ($stmt->errorCode() == 0){
					continue;
				} else return false;
			}
			return true;
		}

		public function deleteFavorites($ids){
			$stmt = $this->connection->prepare(
				"DELETE FROM 1favs WHERE id = :id"
			);

			for ($i = 0; $i < sizeof($ids); $i++){
				$stmt->bindParam(":id", $ids[$i]);
				
				$stmt->execute();
				if ($stmt->errorCode() == 0){
					continue;
				} else return false;
			}
			return true;
		}

		public function getFavorites(){
			//return array();

			$stmt = $this->connection->prepare(
				"SELECT * FROM 1favs ORDER BY isFoil ASC, setcode ASC, cardname ASC"
			);

			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		}

		public function insertNewSet($set){
			message("insertNewSet");
			//$this->connection->query("DELETE FROM 1sets WHERE setcode = '".$set["setcode"]."'");

			$stmt = $this->connection->prepare("
				INSERT INTO 1sets VALUES (0, :setcode, :setname, :foil, :nonfoil, :lastPull, :type)
			");

			$stmt->bindParam(":setcode", $set["setcode"]);
			$stmt->bindParam(":setname",$set["setname"]);
			$stmt->bindParam(":foil", $set["foil"]);
			$stmt->bindParam(":nonfoil", $set["nonfoil"]);
			$stmt->bindParam(":lastPull", $set["lastPull"]);
			$stmt->bindParam(":type", $set["type"]);
			$stmt->execute();
			if ($stmt->errorCode() == 0){
				return $this->connection->lastInsertId();
			} return false;
		}


		public function insertNewCardsWithSetID($setid, $setcode, $cards){

			$stmt = DB::app()->connection->prepare(
				"INSERT INTO 1cards 
					(id, setid, cardname, setcode, rarity)
				VALUES
					(0, :setid, :cardname, :setcode, :rarity)
			");

			$stmt->bindParam(":setid", $setid);
			$stmt->bindParam(":setcode", $setcode);

			for ($i = 0; $i < sizeof($cards); $i++){
				//print_r($cards[$i]); return false;
				$stmt->bindParam(":cardname", $cards[$i]["cardname"]);
				$stmt->bindParam(":rarity", $cards[$i]["rarity"]);
				$stmt->execute();
				if ($stmt->errorCode() == 0){
					continue;
				}
				//message("return false");
				return false;
			}
			return true;
		}

		public function deleteNULLEntries(){

			$stmt = $this->connection->prepare("SHOW TABLES");
			$stmt->execute();

			$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
			//print_r($tables);

			$null = array();
			foreach ($tables as $table){
				$tbn = $table['Tables_in_crawl'];
				//print_r($tbn);
				if ($this->isNoSetTable($tbn)){continue;}
				//message("checking ".$tbn);

				$query = "SELECT * FROM ".$tbn." WHERE cardid IS NULL";
				$stmt = $this->connection->prepare($query);
				$stmt->execute();

				$subResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if (sizeof($subResults)){
					$null[] = $tbn;
					message("null in ".$tbn.": ".sizeof($subResults));
					//print_r($subResults[0]); return;
				}
			}

			for ($i = 0; $i < sizeof($null); $i++){
				$sql = "DELETE FROM ".$null[$i]." WHERE cardid IS NULL";
				$this->connection->exec($sql);
				$sql = "UPDATE 1sets SET lastPull = '0000-00-00' WHERE setcode = ".$null[$i];
				$this->connection->exec($sql);
			}
		}

	    public function dump(){
	    	//Debug::log("dumping DB");
	    	$os = PHP_OS;
			$access = array("root", 147147);

			if ($os != "WINNT"){
				exec('mysqldump -u '.$access[0].' -p'.$access[1].' crawl > '.$_SERVER["DOCUMENT_ROOT"].'/crawl/dump.sql');
			}
			else {
				exec('C:/xampp/mysql/bin/mysqldump -u '.$access[0].' -p'.$access[1].' crawl > '.$_SERVER["DOCUMENT_ROOT"].'/crawl/dump.sql');
			}
	    }
	}
?>
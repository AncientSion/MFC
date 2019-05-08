<?php

	class DB {

		private $connection = null;
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

		public function insertNewFavorites($setCodes, $cardNames, $isFoil){
			$stmt = $this->connection->prepare("
				INSERT into favs 
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

		public function getFavorites(){
			//return array();

			$stmt = $this->connection->prepare(
				"SELECT * FROM favs ORDER BY isFoil ASC, cardname ASC"
			);

			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
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
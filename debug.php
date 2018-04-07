<?php

	function logSearch($codes, $includes, $foil, $depth, $minPrice, $maxPrice, $availChange, $compareType){
		$search = array(
			"type" => "search",
			"options" => array(
				$codes, $includes, $foil, $depth, $minPrice, $maxPrice, $availChange, $compareType
			)
		);

		file_put_contents(__DIR__."/log/mfc.txt", json_encode($search, JSON_NUMERIC_CHECK).",\n", FILE_APPEND);
	}

?>
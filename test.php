<?php

	include_once(__DIR__."\global.php");
	include_once(__DIR__."\simple_html_dom.php");


	$context = stream_context_create(
	    array(
	        "http" =>
				array(
				    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36",
					"method" => "POST",
					"content" => http_build_query(
						array(
							"productFilter[idLanguage]" => array(1, 3),
							"productFilter[isFoil]" => "Y",
							"productFilter[condition]" => array("NM", "EX")
						)
					)
				)
			)
	);

	$time = time();
	$date = date('d.m.Y', $time);
	$time = -microtime(true);

	echo "Script Execution Started \n";


	$cardName = "Maelstrom Pulse";
	$setName = "Alara Reborn";
	$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

	$url = $baseUrl . urlencode($setName) . "/" . urlencode($cardName);
	$html = file_get_html($url, false, $context);
	$table = $html->find("#articlesTable", 0);

	echo $cardName." - ".$setName.", inventory: rows ".(sizeof($table->find("tr"))-1);




?>
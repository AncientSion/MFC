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
							"productFilter[idLanguage]" => array(1),
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


	$cardName = "Keldon Warlord";
	$setName = "Beta";
	$baseUrl = "https://www.cardmarket.com/en/Magic/Products/Singles/";

	$url = $baseUrl . urlencode($setName) . "/" . urlencode($cardName);
	$html = file_get_html($url, false, $context);

	//$foilPrice = $table->children(1)->children(6)->children(1)->children(1)->innertext;

//	$foilPrice = $table->children(1)->find(".st_price", 0)->plaintext;

	$foilPrice = $html->find("#articlesTable", 0)->children(1)->children(5)->children(0)->children(0)->innertext;
	$foilPrice = str_replace(",", ".", substr($foilPrice, 0, strpos($foilPrice, " ")));



	echo $cardName." - ".$setName.", cheapest EN: ".$foilPrice;




?>
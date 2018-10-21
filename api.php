<?php

/*/

rjuQSujRZwugWwsx

0QdYZcbjM9zK7qX4x4tSs1ur6NVaBuBG

KuIKWnjG8G6Sv5XBd9XX7mYyq13LoYYG

Nbx1gkL3XFeDH6cVcbdEXqphaHzWrQhK

*/




//getArticleDetails(319033);
getGames("ALP");


function getGames(){
	$url = "https://api.cardmarket.com/ws/v2.0/output.json/games";

	$data = doConnect($url);
	
	$json = array();
	//var_export($data);

	//$file = fopen(__DIR__."/mkm/input/games.json", "w");
	//fwrite($file, json_encode($data));
	//fclose($file);
	
	foreach ($data->game as $game){
		echo "#" . $game->idGame . " " . $game->name;
		
		//var_export($game->idGame);
		echo "\n";
		
		
		getAllExpansions(($game->name), strtoupper($game->abbreviation), $game->idGame);
		return;
	}
	
	//fwrite($file, json_encode($obj)); fclose($file);
}


function getArticleDetails($id){
	$url = "https://api.cardmarket.com/ws/v2.0/output.json/articles/".$id."?isFoil=1";
	//echo $url;

	$data = doConnect($url);

	//foreach ($data as $key => $value){echo "\n".$key;}

	//echo "\n"; var_export($data);
	echo "\n results: ".sizeof($data); return;
	//var_dump($data); return;

	//$export = array("name" => 

	$file = fopen(__DIR__."/mkm/input/" . $set->code .".json", "w");
	fwrite($file, json_encode($data->single));
	fclose($file);
	//fwrite($file, json_encode($obj)); fclose($file);
}



function getMKMJson($code){
	$set = getSetByCode($code);
	getCardsBySetId($set);
}

function getCardsBySetId($set){
	$url = "https://api.cardmarket.com/ws/v2.0/output.json/expansions/".$set->id."/singles";

	$data = doConnect($url);

	//$export = array("name" => 

	$file = fopen(__DIR__."/mkm/input/" . $set->code .".json", "w");
	fwrite($file, json_encode($data->single));
	fclose($file);
	//fwrite($file, json_encode($obj)); fclose($file);
}


function getSetByCode($code){
	$json = json_decode(file_get_contents(__DIR__."/mkm/expansions.json"));
	for ($i = 0; $i < sizeof($json->expansions); $i++){
		//echo $json->expansions[$i]->code."\n";
		if ($json->expansions[$i]->code == $code){
			return $json->expansions[$i];
		}
	}
}


function getAllExpansions($name, $code, $id){
	$url = "https://api.cardmarket.com/ws/v2.0/output.json/games/".$id."/expansions";
	$data = doConnect($url);

	$obj = array("expansions" => array());
	
	foreach ($data->expansion as $item){
		var_export($data->expansion); return;
		$obj["expansions"][] = array("code" => $code, "name" => $item->enName, "id" => $item->idExpansion);
	}
	$file = fopen(__DIR__."/mkm/input/" . $code .".json", "w");
	fwrite($file, json_encode($obj)); fclose($file);
}


function doConnect($url){
	// Declare and assign all needed variables for the request and the header
	$method = "GET";
	$appToken = "rjuQSujRZwugWwsx";
	$appSecret = "0QdYZcbjM9zK7qX4x4tSs1ur6NVaBuBG";
	$accessToken = "KuIKWnjG8G6Sv5XBd9XX7mYyq13LoYYG";
	$accessSecret = "Nbx1gkL3XFeDH6cVcbdEXqphaHzWrQhK";
	$nonce = uniqid();
	$timestamp = time();
	$signatureMethod = "HMAC-SHA1";
	$version = "1.0";


	// Gather all parameters that need to be included in the Authorization header and are know yet
	$params = array(
		'realm' => $url,
		'oauth_consumer_key' => $appToken,
		'oauth_token' => $accessToken,
		'oauth_nonce' => $nonce,
		'oauth_timestamp' => $timestamp,
		'oauth_signature_method' => $signatureMethod,
		'oauth_version' => $version,
	);

	$baseString = strtoupper($method) . "&";
	$baseString .= rawurlencode($url) . "&";


	// Gather, encode, and sort the base string parameters
	$encodedParams = array();
	foreach ($params as $key => $value){
	   if ("realm" != $key) {
	       $encodedParams[rawurlencode($key)] = rawurlencode($value);
	   }
	}

	ksort($encodedParams);


	//Expand the base string by the encoded parameter=value pairs
	$values = array();
	foreach ($encodedParams as $key => $value){
	   $values[] = $key . "=" . $value;
	}
	$paramsString = rawurlencode(implode("&", $values));
	$baseString .= $paramsString;

	 //Create the signingKey
	$signatureKey = rawurlencode($appSecret) . "&" . rawurlencode($accessSecret);
	   

	// Create the OAuth signature - Attention: Make sure to provide the binary data to the Base64 encoder
	$rawSignature = hash_hmac("sha1", $baseString, $signatureKey, true);
	$oAuthSignature = base64_encode($rawSignature);
	 

	// Include the OAuth signature parameter in the header parameters array
	$params['oauth_signature'] = $oAuthSignature;

	// Construct the header string
	$header = "Authorization: OAuth ";
	$headerParams = array();
	foreach ($params as $key => $value){
	   $headerParams[] = $key . "=\"" . $value . "\"";
	}
	$header .= implode(", ", $headerParams);


	// Get the cURL handler from the library function
	$curlHandle = curl_init();


	// Set the required cURL options to successfully fire a request to MKM's API
	curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curlHandle, CURLOPT_URL, $url);
	curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array($header));
	curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);


	// Execute the request, retrieve information about the request and response, and close the connection
	$content = curl_exec($curlHandle);
	$info = curl_getinfo($curlHandle);
	//foreach ($info as $key => $value){echo $key.": ".$value."\n";}
	curl_close($curlHandle);

	// Convert the response string into an object
	// If you have chosen XML as response format (which is standard) use simplexml_load_string
	//  If you have chosen JSON as response format use json_decode
	//echo $content;
	$decoded = json_decode($content);
	return $decoded;
}


//


/*
$context = stream_context_create(
    array(
        "http" =>
			array(
			    "header" => "Content-Type: application/x-www-form-urlencoded\r\n"."User-Agent: AS-B0T",
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
$url = "https://www.cardmarket.com/robots.txt";
$html = file_get_html($url, false, $context);

echo $html;

*/









/*

include_once(__DIR__."\global.php");


$session = curl_init();
curl_setopt($session, CURLOPT_URL, "http://www.google.com/search?q=curl");
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
curl_setopt($session, CURLOPT_HEADER, false);

$result = curl_exec($session);

curl_close($session);

echo $result;

return;

*/




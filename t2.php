<?php

/*/


App token wdziBklE3HMdO4nP

App secret a0Tn59XW6CbLIwfWyhCPEIqIOiEFY1vX

Access token oC3ORLvDEGVE3dr66AVxxCh3YXSTuEdD

Access token secret SNwiaKosP1yg3ZzZ1OpXkqNljjfyL39X

request : GET https://www.mkmapi.eu/ws/v1.1/account

*/



// Declare and assign all needed variables for the request and the header
$method = "GET";
$url = "https://www.mkmapi.eu/ws/v2.0/output.json/account";
$url = "https://www.mkmapi.eu/ws/v2.0/products/find?search=Springleaf&idGame=1&idLanguage=1";
$url = "https://www.mkmapi.eu/ws/v2.0/output.json/games";
$url = "https://www.mkmapi.eu/ws/v2.0/output.json/games/1/expansions";
$url = "https://www.mkmapi.eu/ws/v2.0/output.json/expansions/1820/singles";
$appToken = "wdziBklE3HMdO4nP";
$appSecret = "a0Tn59XW6CbLIwfWyhCPEIqIOiEFY1vX";
$accessToken = "oC3ORLvDEGVE3dr66AVxxCh3YXSTuEdD";
$accessSecret = "SNwiaKosP1yg3ZzZ1OpXkqNljjfyL39X";
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
//foreach ($info as $key => $value){echo $key.": ".$value."</br>";}
curl_close($curlHandle);

// Convert the response string into an object
// If you have chosen XML as response format (which is standard) use simplexml_load_string
//  If you have chosen JSON as response format use json_decode
$decoded = json_decode($content);
foreach ($decoded as $key => $value){
	echo $key."</br>";
}
foreach ($decoded->single as $item){
	echo $item->enName." => ".$item->idProduct."</br>";
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




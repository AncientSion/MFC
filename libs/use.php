<?php

include_once("simple_html_dom.php");


$data = file_get_contents("crawler/output.json");
$data = json_decode($data, TRUE);

?>
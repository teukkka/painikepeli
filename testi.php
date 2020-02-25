<?php

if ($_SERVER['REQUEST_METHOD']==='POST'){
	$data = file_get_contents('php://input');
	$response["error"]="false";
	$response["pisteet"]=20;
	$response["name"]=$data;
	$response["voittoon"]=40;
	$response["voitto"]=40;
	echo json_encode($response);
}
?>

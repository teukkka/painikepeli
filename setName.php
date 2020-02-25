<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	#tarkastaa onko nimi alkio olemassa(tarkistetaan virhetilanteiden käsittelyn vuoksi) 
	$data = file_get_contents('php://input');
	if (empty($data)) {
		$response["error"]=True;
		$response["viesti"]="nimi puuttuu";
	}
	else {
		$receivedname=$data;
		#yritetään lisätä nimi tietokantaan ja luodaan vastaus pelaajalle
		switch (addtodb($receivedname)){
			case "name_exists":
				$response["error"]=True;
				$response["msg"]="nimi on jo käytössä";
				#name already in use
				break;

			#ilmoita pelaajalle että nimi on lisätty
			case "name_added":
				$response["error"]=False;
				$response["msg"]="nimi lisätty onnistuneesti";
				$response["points"]=20;
				break;

			case "conn_fail":
				#tietokanta ei vastannut
				$response["error"]=True;
				$response["msg"]="yritä uudelleen";
				break;
			default:
				#unexpected error
				$response["error"]=True;
				$response["msg"]="valitse toinen nimi";

		}
	
	}
	#lähettää vastauksen pelaajalle
	echo json_encode($response);

}

function addtodb($data){
	#heroku postgres parametrit
	$server = "ec2-54-246-89-234.eu-west-1.compute.amazonaws.com";
	$username = "jueimakqwspryl";
	$password = "bf8d7fc5d6b13c63061b6f0a9d3fe240d16379d26d4d946a3a48dd23b54b8120";
	$dbname = "dfnl9iq6jfq97g";

	#avaa yhteyden tietokantaan
	$conn = new mysqli($server, $username, $password, $dbname);

	#tarkistaa onko yhteys luotu onnistuneesti
	if ($conn->connect_error) {
		die("connection failed: " . $conn->connect_error);
		return "conn_fail";
	}

	#haetaan nimeä tietokannasta
	$sql= "SELECT nimi FROM pelaajatiedot WHERE $data";
	$result = $conn->query($sql);

	#tarkistetaan onko nimi jo tietokannassa vai ei
	if($result->num_rows === 1){
		return "name_exists";
	}

	elseif ($result->num_rows === 0) {
		#lisätään nimi tietokantaan
		$sql="INSERT INTO pelaajatiedot VALUES($receivedname,'20')";
		if ($conn->query($sql) === True) {
			return "name_added";
		}
		else {
			return "conn_fail";
		}
	}

	#jos tietokannasta löytyy nimellä useampi rivi on jotain mennyt pieleen ja nimeä ei voi käyttä
	else{
		return False;
	}

}


?>

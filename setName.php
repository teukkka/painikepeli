<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	#hakee nimen vastaanotetusta post metodista 
	#ja jos nimi puuttuu palauttaa tiedon virheestä käyttöliittymälle
	$receivedname= file_get_contents('php://input');
	if (empty($receivedname)) {
		$response["error"]=True;
		$response["msg"]="nimi puuttuu";
	}
	else {
		#yritetään lisätä nimi tietokantaan ja luodaan vastaus pelaajalle
		#kts. funktio add to db
		#vastauksissa error kenttä on oleellinen msg kenttä on debugaamista varten
		switch (addtodb($receivedname)){
			case "name_exists":
				#nimi on jö käytössä
				$response["error"]=True;
				$response["msg"]="nimi on jo käytössä";
				break;

			#ilmoita pelaajalle että nimi on lisätty
			case "name_added":
				$response["error"]=False;
				$response["msg"]="nimi lisätty onnistuneesti";
				$response["name"]=$receivedname;
				break;

			case "conn_fail":
				#tietokanta ei vastannut
				$response["error"]=True;
				$response["msg"]="yritä uudelleen";
				break;
			default:
				#tapahtui määrittelemätön virhe
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

	$conn_string = "host=$server port=5432 dbname=$dbname user=$username password=$password";
	
	#avaa yhteyden tietokantaan
	$conn = pg_connect($conn_string);

	#haetaan nimeä tietokannasta
	$sql= "SELECT nimi FROM pelaajatiedot WHERE $1";
	if(!$result = pg_query_params($conn, $sql, array($data))){
		#lisätään nimi tietokantaan jos nimeä ei löydy
		$sql="INSERT INTO pelaajatiedot VALUES($1,'20')";
		if (pg_query_params($conn, $sql, array($data))) {
			return "name_added";
		}
		else {
			return "conn_fail";
		}
	}

	#jos nimellä löytyi yksi rivi palautetaan nimi on olemassa tieto pelaajalle
	if(pg_num_rows($result) === 1){
		return "name_exists";
	}

	#jos tietokannasta löytyy nimellä useampi rivi on jotain mennyt pieleen ja nimeä ei voi käyttää
	#palautetaan pelaajalle ilmoitus virheestä
	else{
		return False;
	}

}


?>

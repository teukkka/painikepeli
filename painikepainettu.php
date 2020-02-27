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
		$response=button_pushed($receivedname);
		}
	
	#lähettää vastauksen pelaajalle
	echo json_encode($response);

}

function button_pushed($name){
	#heroku postgres parametrit
	$server = "ec2-54-246-89-234.eu-west-1.compute.amazonaws.com";
	$username = "jueimakqwspryl";
	$password = "bf8d7fc5d6b13c63061b6f0a9d3fe240d16379d26d4d946a3a48dd23b54b8120";
	$dbname = "dfnl9iq6jfq97g";

	#avaa yhteyden tietokantaan
	$conn_string = "host=$server port=5432 dbname=$dbname user=$username password=$password";
	$conn = pg_connect($conn_string);

	#tarkistaa onko yhteys luotu onnistuneesti
	if ($conn->connect_error) {
		die("connection failed: " . $conn->connect_error);
		return "conn_fail";
	}

	if ($pisteet=get_player_points($conn,$name)==0){
		$response["error"]=True;
		$response["pisteet"]=$pisteet;
		$conn->close();
		return $response;
	}

	$sql="BEGIN TRANSACTION; LOCK TABLE laskuri IN ACCESS EXCLUSIVE MODE;";
	pg_query($conn, $sql);
	#haetaan nimeä tietokannasta
	$sql= "UPDATE laskuri SET luku = luku + 1 WHERE laskuri_id='1'";
	pg_query($conn, $sql);

	$sql= "SELECT luku FROM laskuri WHERE laskuri_id='1'";
	$result = pg_query($conn, $sql);



	#tarkistetaan onko nimi jo tietokannassa vai ei
	if(pg_num_rows($result) === 1){
		$laskurinarvo= $result->fetch_assoc()["luku"];
		if (($laskurinarvo)%500==0){
			$add_points=249;
			if (update_players_points($conn, $add_points, $name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=False;
				$response["pisteet"]=$pisteet+$add_points;
				$response["voittoon"]=next_win($laskurinarvo);
				$response["viesti"]="voitit 250 pistettä";
				$response["voitto"]=250;
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
			}
		}
		elseif(($laskurinarvo)%50==0){
			$add_points=39;
			if (update_players_points($conn, $add_points, $name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=False;
				$response["pisteet"]=$pisteet+$add_points;
				$response["voittoon"]=next_win($laskurinarvo);
				$response["viesti"]="voitit 40 pistettä";
				$response["voitto"]=40;
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=True;
			}

		}
		elseif(($laskurinarvo)%10==0){
			$add_points=4;
			if (update_players_points($conn,$add_points,$name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=False;
				$response["pisteet"]=$pisteet+$add_points;
				$response["voittoon"]=next_win($laskurinarvo);
				$response["viesti"]="voitit 5 pistettä";
				$response["voitto"]=5;
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=True;
			}

		}
		else{
			$add_points=-1;
			if (update_players_points($conn,$add_points,$name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=False;
				$response["pisteet"]=$pisteet+$add_points;
				$response["voittoon"]=next_win($laskurinarvo);
				$response["viesti"]="ei voittoa";
				$response["voitto"]=0;
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=True;
			}
		}
	$conn->close();
	return $response;
	}


	#jos tietokannasta löytyy nimellä useampi rivi on jotain mennyt pieleen ja nimeä ei voi käyttä'. Tämä ei kuitenkaan tulisi olla mahdollista sillä laskuri_id on primary key jotka ovat yksilöllisiä.
	else{
		$conn->close();
		return False;
	}

}

function update_players_points($conn,$add_points,$name){
	$sql= "UPDATE pelaajatiedot SET pisteet = pisteet + $1 WHERE nimi=$2";
	if (pg_query_params($conn, $sql, array($add_points, $name))) {
		return True;
	}
	else{
		return False;
	}

}

function get_player_points($conn,$name){
	$sql="SELECT pisteet FROM pelaajatiedot WHERE nimi=$1";
	$result=pg_query_params($conn, $sql, array($name));
	$pisteet=$result->fetch_assoc()["pisteet"];
	return $pisteet;
}

function next_win($laskurinarvo){
	$win1=500-($laskurinarvo%500);
	$win2=50-($laskurinarvo%100);
	$win3=10-($laskurinarvo%10);
	return min($win1,$win2,$win3);
}



?>

<?php

#tarkistetaan onko post metodia käytetty 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	#haetaan nimi vastaanotetusta palvelinpyynnöstä ja palautetaan
	#virhe mikäli tietoa ei löydy
	$data = file_get_contents('php://input');
	if (empty($data)) {
		$response["error"]=True;
		$response["viesti"]="nimi puuttuu";
	}
	else {
		#jos nimi vastaanotettiin yritetään lisätä nimi tietokantaan ja luodaan vastaus pelaajalle
		$response=button_pushed($data);
		}
	
	#muuttaa vastauksen json muotoon ja lähettää vastauksen pelaajalle
	echo json_encode($response);

}

function button_pushed($name){
	#heroku postgres parametrit
	$server = "ec2-54-246-89-234.eu-west-1.compute.amazonaws.com";
	$username = "jueimakqwspryl";
	$password = "bf8d7fc5d6b13c63061b6f0a9d3fe240d16379d26d4d946a3a48dd23b54b8120";
	$dbname = "dfnl9iq6jfq97g";


	#avaa yhteyden parametrien määrittämään tietokantaan
	$conn_string = "host=$server port=5432 dbname=$dbname user=$username password=$password";
	$conn = pg_connect($conn_string);

	#määritetään muuttujille alkuarvot ehkäistäkseen virheitä
	$add_points=0;
	$voittoon=0;

	#hakee pelaajan pisteet kts. functio get_player_points
	$pisteet=get_player_points($conn,$name);

	#tarkistetaan oliko pelaajan pisteet 0. eli toisin sanoen aloittiko pelaaja uuden pelin
	#ja palautetaan laadittu vastaus lähetettäväksi käyttöliittymälle
	if ($pisteet==0){
		$response=create_response($pisteet, 0, $laskurinarvo, 0);

		pg_close($conn);
		return $response;
	}

	#jos pelaajalla oli pisteitä jäljellä aloitetaan transaktio pelin laskuriluvun
	#korottamiseksi ja pelaajan pisteiden muuttamiseksi
	#transaktio lukitsee tietokannan sen suorituksen ajaksi jolloin voidaan välttää
	#race condition joka voi vaikuttaa esimerkiksi laskurilta haettuun arvoon
	#transaktio huolehtii myös siitä jos jokin menee vikaan niin tietokanta palautetaan
	#tilaan ennen transaktion aloittamista
	$sql="BEGIN TRANSACTION; LOCK TABLE laskuri IN ACCESS EXCLUSIVE MODE;";
	pg_query($conn, $sql);

	#korotetaan laskurin arvoa yhdellä
	$sql= "UPDATE laskuri SET luku = luku + 1 WHERE laskuri_id='1'";
	pg_query($conn, $sql);

	#haetaan laskurin arvo päivityksen jälkeen
	$sql= "SELECT luku FROM laskuri WHERE laskuri_id='1'";
	$result = pg_query($conn, $sql);



	#jos laskureita löytyi yksi haetaan sen arvo ja tarkistetaan
	#voittaako pelaaja. tämän jälkeen pelaajan pisteet päivitetään
	#ja jos päivitys onnistuu luodaan vastaus käyttöliittymälle ja
	#palautetaan se lähetettäväksi
	if(pg_num_rows($result) === 1){
		$laskurinarvo= pg_fetch_assoc($result)["luku"];
		if (($laskurinarvo)%500==0){
			$add_points=249;
			if (update_players_points($conn, $add_points, $name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response=create_response($pisteet, $add_points, $laskurinarvo, 250);
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=True;
			}
		}
		elseif(($laskurinarvo)%50==0){
			$add_points=39;
			if (update_players_points($conn, $add_points, $name)){
				$sql="COMMIT TRANSACTION";
				pg_query($conn, $sql);
				$response=create_response($pisteet, $add_points, $laskurinarvo, 40);
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
				$response=create_response($pisteet, $add_points, $laskurinarvo, 5);
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
				$response=create_response($pisteet, $add_points, $laskurinarvo, 0);
			}
			else{
				$sql="ROLLBACK TRANSACTION";
				pg_query($conn, $sql);
				$response["error"]=True;
			}
		}
	#sulkee yhteyden ja palauttaa luodun vastauksen
	pg_close($conn);
	return $response;
	}


	#jos tietokannasta löytyy nimellä useampi rivi on jotain mennyt pieleen ja nimeä ei voi käyttä'.
	#Tämä ei kuitenkaan tulisi olla mahdollista sillä laskuri_id on primary key jotka ovat yksilöllisiä.
	else{
		pg_close($conn);
		return create_response($pisteet, $add_points, $laskurinarvo, 40);
	}

}

#funktio hakee pelaajan pisteet tietokannasta ja palauttaa pisteet button pushed funktiolle riville 34.
#jos kuitenkin pelaajan pisteet ovat 0 eli pelaaja on aloittamassa uutta peliä
#asetetaan pelaajan pisteiksi 20 ja palautetaan pisteet ennen uusien pisteiden asetusta eli 0 pistettä
function get_player_points($conn,$name){
	$sql="SELECT pisteet FROM pelaajatiedot WHERE nimi=$1";
	$result=pg_query_params($conn, $sql, array($name));
	$pisteet=pg_fetch_assoc($result)["pisteet"];
	if ($pisteet<1){
		$sql= "UPDATE pelaajatiedot SET pisteet = 20 WHERE nimi=$1";
		$result=pg_query_params($conn, $sql, array($name));
	}
	return $pisteet;
}

#funktio päivittää pelaajan pisteet tietokantaan painalluksen jäljiltä
#ja palauttaa tiedon onnistuiko päivitys vai ei
function update_players_points($conn,$add_points,$name){
	$sql= "UPDATE pelaajatiedot SET pisteet = pisteet + $1 WHERE nimi=$2";
	if (pg_query_params($conn, $sql, array($add_points, $name))) {
		return True;
	}
	else{
		return False;
	}

}

#funktio laskee painallusten määrän seuraavaan voittoon joka on aina enintään 10
#joten riittäisi laskea vain 10-laskurinarvo%10 mutta jos jatkossa
#halutaan muuttaa arvoja joilla palkintoja voitetaan ehkäisee tämä toteutus virheitä
#ja helpottaa arvojen muuttamista
function next_win($laskurinarvo){
	$win1=500-($laskurinarvo%500);
	$win2=50-($laskurinarvo%50);
	$win3=10-($laskurinarvo%10);
	return min($win1,$win2,$win3);
}

#funktio luo pelaajalle palautettavan viestin joka sisältää tiedon
#tapahtuiko virhettä, pelaajan pisteet, painalluksia seuraavaan voittoon 
#ja paljonko pisteitä pelaaja voitti
function create_response($pisteet, $add_points, $laskurinarvo, $voitto){
	$response["error"]=False;
	$response["pisteet"]=$pisteet+$add_points;
	$response["voittoon"]=next_win($laskurinarvo);
	$response["voitto"]=$voitto;
	return $response;
}

?>

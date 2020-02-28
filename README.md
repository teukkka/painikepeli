#Painikepeli 

Painikepeli on lisätty herokuun testattavaksi.
Tässä git repositoryssä on toteutettu palvelimelle asetettavat php tiedostot joista
Painikepainettu.php toteuttaa seuraavat osat painikepelin toiminnasta:

-Pitää huolen että pelaajan pistesaldo voi olla ainoastaan positiivinen
-Pelaaja voittaa enimmillään yhden palkinnon per painallus
ja saa pisteet suurimman painalluksella saavuttaman voiton mukaisesti
-painiketta painaessa pelaajalta vähennetään yksi psite, laskurin arvo kasvaa yhdellä
-pelaajalle ilmoitetaan mahdollisesta voitosta
-pelaajalle ilmoitetaan vaadittujen painallusten määrä seuraavaan voittavaan arvoon

setName.php puolestaan ei suoranaisesti liity painikepelin toimintaan muuten kuin 
eritelläkseen pelaajat toisistaan tietokannassa

index.php on ainoastaan tyhjä tiedosto herokua varten.

testi.php on tiedosto jota on käytetty debugaamiseen.

Painikepelin_suunnitelma.txt on alkuperäinen suunnitelma jonka pohjalta kokonaisuutta lähdettiin rakentamaan.

postgreSQL.txt pitää sisällään komentoja joilla luotiin tietokannan taulut.

Pelin käyttöliittymä on toteutettu mobiilisovelluksena joka löytyy täältä:
[Painikepeli mobiilisovellus](https://github.com/teukkka/android_painikepeli)


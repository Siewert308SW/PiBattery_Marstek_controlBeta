![Banner](https://github.com/user-attachments/assets/143c1ac7-f58b-4016-88dd-2aac3e8cd6f2)

# PiBattery – Eenvoudige Zelfbouw Thuisbatterij

**PiBattery**  
Er is niets zo veranderlijk als de energiemarkt, en Den Haag is nog wispelturiger dan het Nederlandse weer.  
Met het naderende einde van de salderingsregeling en de invoering van terugleverkosten zocht ik naar een betaalbare oplossing om mijn energiekosten te drukken.  
Door de stijgende kosten en de maatregelen van overheid en energiebedrijven wordt thuis energie opslaan steeds aantrekkelijker.  
De prijzen van kant-en-klare thuisbatterijen waren voor mij in 2023 nog niet aantrekkelijk genoeg, en ik verwachtte dat die met de toenemende populariteit van thuisbatterijen alleen maar zouden stijgen.  
Een zelfbouw-thuisbatterij is/was een betaalbaar alternatief om overtollige zelfopgewekte energie op te slaan.  
Tijdens mijn zoektocht kwam ik in een thread op Tweakers een goedkope oplossing tegen, en daarop voortbordurend wil ik graag mijn setup en scripts met jullie delen.  
Maar sinds 2026 zijn de prijzen sterk gedaald en het aanbod van kant-en-klare stekkerbatterijen gegroeid.  
Doordat mijn situatie thuis veranderd was en er meer batterijcapaciteit nodig was, was het nu goedkoper om een kant-en-klare batterij aan te schaffen dan mijn huidige setup uit te breiden.  
Vandaar dat ik sinds april 2026 een Marstek Venus E v3.0 5,12 kWh heb toegevoegd.  
Hierdoor moest mijn bestaande aansturing herschreven worden om de Marstek te integreren en aan te sturen via de lokale ModBus API.  
Ik leg hier de simpele basisprincipes uit; voor diepgaande details ga ik ervan uit dat wie hiermee aan de slag wil, enige technische kennis en ervaring met PHP heeft.

---

## Doel

**Het doel** van deze setup is om in de nachten en avonden te sturen op NOM (Nul Op de Meter),  
en overdag de grootste verbruikspieken af te vlakken.  
En natuurlijk zoveel mogelijk PV-overschot op te slaan.  
Handelen (inkoop/verkoop) in combinatie met een dynamisch contract is niet mijn doel.

Ik probeer vooral de maandelijkse kosten te drukken door het overschot van mijn zonnepanelen op te slaan in de batterij, zodat ik minder terugleverkosten betaal.  
De opgeslagen energie gebruik ik 's avonds en 's nachts weer, wat mijn afname van het net vermindert.  
Zo hoop ik de maandelijkse energiekosten verder te verlagen.

De setup die ik hier beschrijf lijkt op een kant-en-klare stekkerbatterij zoals die van HomeWizard of Marstek,  
maar is een zelfbouwvariant zonder gelikt kastje.  
Met nu in 2026 als aanvulling een Marstek erbij die via de lokale ModBus API wordt aangestuurd en meehelpt.  
In tegenstelling tot de grote merken is dit geen off-grid systeem.  
Ik gebruik twee EcoFlow Powerstream 800W omvormers.  
Omdat dit stekkeromvormers zijn, is de netinjectie net als bij kant-en-klare oplossingen beperkt tot 800W.  
Maar in een configuratie als de mijne (25,6V batterijen) is dat begrensd tot 600W per omvormer.  
De Marstek is op een andere fase aangesloten maar niet op zijn eigen groep, en is eveneens begrensd op 800W.

<p align="center">
<img src="images/zelfconsumptie.png" alt="Mijn verbruik" width="75%">
</p>
<p align="center"><small>Een productie/verbruiksdag</small></p>

---

## Basiswerking

**Het laden** is eenvoudig:  
De PHP-scripts berekenen aan de hand van de instellingen en configuratie of er geladen mag worden.  
Zo starten de laders op basis van het PV-overschot en wordt er onderscheid gemaakt of eerst de Marstek of de piBattery geladen mag worden.  
Het script kiest zelf de beste ladercombinatie, waarbij gekozen kan worden welke lader de master is.  
Dat wil zeggen dat alle beschikbare combinaties tenminste de master bevatten.

- **350W**
- **700W**
- **1000W**
- **1300W**
- **1600W**

Allemaal afhankelijk van het beschikbare overschot.  
Op basis van diverse berekeningen kunnen de laders individueel in- en uitgeschakeld worden.  
De laders zijn allemaal via hun eigen HomeWizard-socket aangesloten.  
Het schakelen gebeurt via de lokale HomeWizard API.  
Er is een schakelpauze om onnodig schakelen te voorkomen.  
Verder berekent het script het laadverlies, zodat in de debug-output het correcte batterij-SOC en de laad- en ontlaadtijden weergegeven worden.

**Het ontladen** is nog eenvoudiger:  
Op basis van het P1-verbruik wordt bepaald of er te veel van het net wordt verbruikt.  
Hierop wordt het benodigde vermogen via de EcoFlow API naar de omvormers gestuurd.  
Op deze manier stuurt het systeem aan op NOM (Nul Op De Meter).  
Er wordt rekening gehouden met zomer- en wintertijd;  
bijvoorbeeld dat de batterij in de winter minder diep wordt ontladen om slechte PV-dagen te overbruggen.  
Ook met korte stroompieken in huis wordt rekening gehouden: het script reageert niet direct, om onnodig schakelen te voorkomen.

In totaal heb ik nu een injectievermogen van:

- **1200W** – 2x EcoFlow micro-omvormers
- **800W**  – Marstek Venus E v3.0

Totaal **2000W** — genoeg om bijna alle zware huishoudelijke apparaten af te dekken.

---

## Functies & Mogelijkheden

- **Volautomatisch laden/ontladen** van de batterijen op basis van P1-verbruik en zonne-opbrengst
- **Slimme schakeling** van laders en omvormers via HomeWizard P1-meter, kWh-meter, energy-sockets en directe API-aansturing
- **Laadverliesmeting** wordt automatisch berekend en gecorrigeerd op basis van laden en ontladen
- **Marstek ModBus-integratie**: de Marstek Venus E v3.0 wordt aangestuurd via ModBus over LAN (poort 502)
- **Ondersteuning voor Domoticz**: actuele batterijstatus en energiegegevens worden doorgegeven aan Domoticz via `helpers.php`
- **Pauzefunctie en tijdschema's**: voorkomt onnodig schakelen bij wolkendips
- **Meertaligheid**: zowel Nederlands als Engels
- **Uitgebreide logging en debug-output** voor probleemoplossing en finetuning
- **Fasebescherming**: laders worden direct uitgeschakeld als de fase waarop ze aangesloten zijn te veel vermogen trekt
- **BMS Wake-functie**: houdt het BMS actief bij lage batterijspanning
- **Extra koeling**: 12cm PC-fans op de omvormers worden automatisch aangestuurd

---

## Mijn Setup & Kosten

- 1x Marstek Venus E v3.0 5,12 kWh
- 1x Raspberry Pi 4 met behuizing, 64GB USB-opslag en adapter
- 1x HomeWizard P1-meter
- 1x HomeWizard 3-fase kWh-meter (realtime PV-opwek uitlezen)
- 6x HomeWizard Energy-sockets
- 2x EcoFlow Powerstream 800W omvormers
- 2x EcoFlow Coolingdecks
- 2x 12cm USB-powered PC-fans
- 2x Victron IP22 12A laders
- 1x Powerqueen 20A LiFePO4-lader
- 3x Powerqueen 25,6V 100Ah LFP-batterijen in parallel
- Klein materiaal zoals zekeringen, bekabeling, busbars, batterijschakelaar, etc.

Kosten voor deze 7,5 kWh thuisbatterij waren, door slim inkopen, **€2100**.  
Marstek was op moment van kopen in maart 2026 **€1100**.  
Totale kosten voor effectief **11 kWh** aan opslag en **2000W** injectievermogen: **€3200**.

<p align="center">
<img src="images/setup2.jpg" alt="Mijn setup" width="75%">
</p>

<p align="center">
<img src="images/display.jpg" alt="Mijn setup" width="75%">
</p>

<p align="center"><small>Mijn setup in de garage</small></p>

---

## Vereisten

Voordat je begint, zorg je dat het volgende aanwezig en geconfigureerd is:

- **Raspberry Pi** (of andere Linux SBC/VM) met **PHP 8.x CLI** inclusief `php-curl`
- **HomeWizard** P1-meter, kWh-meter en energy-sockets met lokale API ingeschakeld
- **EcoFlow API-toegang** via het [EcoFlow IoT Developer Platform](https://developer-eu.ecoflow.com/us/) (gratis account aanmaken, access key + secret key ophalen)
- **Marstek Venus E v3.0** bereikbaar op het lokale netwerk via **ModBus over LAN (poort 502)**  
  *(stel een vast IP-adres in voor de Marstek in je router)*
- **(Optioneel)** Domoticz draaiend op het lokale netwerk voor logging en visualisatie

---

## Installatie

De installatie wordt hier niet tot in detail besproken;  
ik ga ervan uit dat de gebruiker enige technische kennis heeft.  
Samengevat:

1. **Raspberry Pi** (of andere Linux SBC/VM) met PHP 8.x + php-curl
2. Kloon of download de repository naar bijvoorbeeld `/home/siewert/pibatteryMarstekModbus/`
3. Kopieer `config/config.php` en vul alle IP-adressen, API-keys en instellingen in  
   *(let op: commit `config.php` nooit naar Git — voeg het toe aan `.gitignore`)*
4. **HomeWizard** API-aansturing voor P1-meter, kWh-meter & energy-sockets staat aan
5. **EcoFlow API** is opengesteld via het EcoFlow IoT Developer Platform
6. **Marstek** is bereikbaar op het netwerk en ModBus is actief op poort 502
7. Stel de cronjob in (zie hieronder)
8. (Optioneel) **Domoticz** voor het doorsturen en loggen van alle data
9. **Elektrisch** deel vereist kennis; vertrouw je het niet, laat dit door een expert uitvoeren

De batterijen zijn parallel aangesloten en verbonden met de PV-ingang van de omvormers.  
De omvormers zijn met hun stekker in een HomeWizard energy-socket gestoken om realtime output uit te lezen.  
Elke omvormer is op een vrije groep aangesloten.  
De laders zijn parallel aangesloten op de batterijen en hebben elk hun eigen HomeWizard energy-socket, om de realtime output te meten én ze te kunnen in- en uitschakelen.  
De Powerqueen-batterijen hebben hun eigen BMS en zijn elk individueel afgezekerd met een 30A zekering.

<p align="center">
<img src="images/thuisbatterij.png" alt="Voorbeeld" width="75%">
</p>
<p align="center"><small>Bron: Foto van ehoco.nl</small></p>

Hier vind je een handig linkje met een uitgebreide uitleg:  
https://ehoco.nl/eenvoudige-thuisbatterij-zelf-maken/

---

## Bestands- en Mappenstructuur

```
pibatteryMarstekModbus/
│
├── pibattery.php                    # Hoofdscript — aanroepen via cronjob (elke 20 sec)
├── bootstrap/
│   └── bootstrap.php               # Opstart-initialisatie, laadt alle dependencies
├── class/
│   ├── ecoflow_api_class.php       # EcoFlow API-integratie (door Thijsmans, CC BY-NC 4.0)
│   └── marstek_modbus_class.php    # Marstek ModBus over LAN-integratie
├── config/
│   └── config.php                  # Alle instellingen (hardware, batterijen, API-keys, etc.)
├── data/
│   ├── charge_sessions.json        # Laadsessie-registratie
│   ├── domoticz_state.json         # Gecachte Domoticz-waarden (voorkomt onnodige updates)
│   ├── pibattery.lock              # Lockfile — voorkomt overlappende script-runs
│   ├── runTimer_Daytime.json       # Timer voor dagelijkse schakelpauzes
│   ├── runTimer_Nighttime.json     # Timer voor nachtelijke schakelpauzes
│   ├── timeStamp.json              # Tijdsregistratie van laatste runs
│   └── variables.json             # Dynamische variabelen en tijdelijke statusdata
├── includes/
│   ├── functions.php               # Algemene functies (HomeWizard, berekeningen, debug)
│   ├── helpers.php                 # Fase-bescherming, BMS-wake, laadtijden, Domoticz-push
│   └── variables.php              # Initialisatie van alle variabelen vanuit opgeslagen data
└── scripts/
    ├── baseload.php                # Baseload-sturing: bepaalt ontlaadvermogen omvormers
    └── charge.php                  # Laadlogica: ladersselectie en batterijbeheer
```

---

## Configuratie

Alle belangrijke instellingen vind je in `config/config.php`.  
Hier stel je onder andere in:

- IP-adressen en API-keys voor HomeWizard en EcoFlow
- Seriënummers van de EcoFlow-omvormers
- IP-adres van de Marstek (ModBus over LAN, poort 502)
- Batterijcapaciteit, type en spanning (piBattery én Marstek)
- Laad-/ontlaadregimes (dag/nacht), minimum SOC-percentages
- Pauzetijden, hysterese en drempelwaarden
- Optionele Domoticz-koppeling (IP en IDX-nummers van dummy devices)
- Via een terminal kun je het script ook handmatig aanroepen om te finetunen en te debuggen

<p align="center">
<img src="images/terminal.png" alt="Terminal output" width="35%">
</p>
<p align="center"><small>Terminal output</small></p>

<p align="center">
<img src="images/domoticz.png" alt="Domoticz" width="35%">
</p>
<p align="center"><small>Domoticz</small></p>

---

## Cronjob

Het hoofdscript wordt elke **10 seconden** uitgevoerd via drie cron-regels:

```bash
* * * * * sudo php /pad/naar/pibattery.php > /dev/null
* * * * * (sleep 10; sudo php /pad/naar/pibattery.php) > /dev/null
* * * * * (sleep 20; sudo php /pad/naar/pibattery.php) > /dev/null
* * * * * (sleep 30; sudo php /pad/naar/pibattery.php) > /dev/null
* * * * * (sleep 40; sudo php /pad/naar/pibattery.php) > /dev/null
* * * * * (sleep 50; sudo php /pad/naar/pibattery.php) > /dev/null
```

---

## Domoticz-integratie (optioneel)

Wil je actuele waarden in Domoticz zien?  
Vul dan je Domoticz-IP en de juiste IDX-nummers in voor de gewenste dummy devices in `config/config.php`.  
De Domoticz-updates worden automatisch verzorgd vanuit `includes/helpers.php` — dit gebeurt alleen als er iets gewijzigd is, zodat Domoticz niet onnodig belast wordt.

De volgende waarden worden naar Domoticz gepusht:

- Batterij SOC (piBattery & Marstek)
- Beschikbare energie (piBattery & Marstek)
- Batterijspanning
- Laad- en ontlaadvermogen (piBattery & Marstek)
- Geschatte laad- en ontlaadtijd (piBattery & Marstek)
- Round-Trip Efficiency (RTE)

---

## Bijdragen & Licentie

Dit project is open source en bedoeld om samen te verbeteren.  
Pull requests, feedback en suggesties zijn welkom.

> **Let op:** De `ecoflow_api_class.php` is beschikbaar gesteld door Thijsmans onder de **CC BY-NC 4.0** licentie (Attribution-NonCommercial). Houd hier rekening mee bij hergebruik.

---

## Grote dank

Mijn dank voor dit project gaat uit naar:
- **Thijsmans** voor het beschikbaar stellen van de EcoFlow API-aansturing
- **ehoco.nl** voor de inspiratie voor dit project
- **salipander** voor het starten van het topic "Eenvoudige thuisaccu samenstellen" op Tweakers
- En allen die ik vergeten ben ;-)

---

## Handige linkjes

Om je op weg te helpen en inspiratie op te doen, hier enkele handige links waarop ik mijn project gebaseerd heb:

- Tweakers: [Eenvoudige thuisaccu samenstellen](https://gathering.tweakers.net/forum/list_messages/2253584/0)
- ehoco.nl: [Een eenvoudige thuisbatterij zelf maken](https://ehoco.nl/eenvoudige-thuisbatterij-zelf-maken/)
- EcoFlow IoT: [IoT Developer Platform](https://developer-eu.ecoflow.com/us/)

---

**Veel plezier met mijn PiBattery-bijdrage!**

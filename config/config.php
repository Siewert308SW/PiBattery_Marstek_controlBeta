<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                        Config variables                       //
// **************************************************************//
//                                                               //

// = Debug?
	$debug                  = 'yes';        					 // Value 'yes' or 'no'
	
// = Location variables
	$latitude               = '00.00000';   					 // Latitude
	$longitude              = '-0.00000';   					 // Longitude
	$zenitLat               = '89.5';       					 // Zenith latitude: the highest point of the sky as seen from the observer’s location
	$zenitLong              = '91.7';       					 // Zenith longitude: the highest point of the sky as seen from the observer’s location
	$timezone               = 'Europe/Amsterdam'; 				 // My php.ini doesn't apply the timezone, so it’s set manually here

// = Battery variables
	$batteryVolt            = 25.6;         					 // Battery Voltage
	$batteryAh              = 300;          					 // Total Ah of all batteries
	$batteryMinimum         = 15;           					 // Minimum percentage to keep in the battery, wintertime will be automaticly set to 25%
	$marstekMinimum         = 18;

	$batteryVoltMax         = 27.0; 
	$batteryVoltTrigger     = 25.3;
	$batteryVoltMin         = 23.0;
	
// = Inverter variables
	$ecoflowOneMaxOutput   	= 580;
	$ecoflowTwoMaxOutput   	= 580;
	$marstekMaxOutput      	= 800;
	$ecoflowMinOutput      	= 150;         					     // Minimum output (Watts); the inverter is allowed to deliver
	$ecoflowOutputOffSet   	= 1;           					 	 // Subtract this value (Watts) from the new baseload: this part is always imported from the grid to prevent injection
	$ecoflowMaxInvTemp     	= 65;           					 	 // Maximum internal temperature (°C); inverter stops feeding above this temperature
	
// = Charger variables
	$chargerhyst            = 150;          					 // Only turn off chargers if import exceeds this many Watts (prevents flip-flopping)
	$chargerWattsIdle       = 80;          					     // Standby Watts of all chargers when the batteries are full
	$chargerPausePct        = 85;           					 // When battery has been charged 100% till what % has it to drop before charging is allowed again
	$chargeSessions			= 5;                                 // How many charge session to calculate charging loss 
	$chargerPause           = 60;          					 	 // Delay in seconds before toggling chargers (prevents flip-flops), But only if realUsage is lower then 2500w
	
// = Baseload variables
	$baseloadDelta			= 20;
	
// = Phase protection
	$faseProtection         = 'yes';        				     // Value 'yes' or 'no'
	$maxFaseWatts           = 4500;         				     // If 'yes' whats the max Watts to guard, all chargers are turned off to prevent overloading
	$fase                   = 1; 
	$fase2                  = 2; 
	$fase3                  = 3;
 	
// = BMS Voltages
	$bmsKeepAwake           = 'yes';        				     // Value 'yes' or 'no'
	$bmsWakeVoltOn  		= 22.0;  							 // BMS minimum voltage at which 1 charger will keep BMS awake
	$bmsWakeVoltOff 		= 23.5;  							 // BMS stop voltage at wich 1 charger will stop charging

// = HomeWizard variables
	$hwP1IP                 = '192.168.178.1';     			 // HomeWizard P1-meter IP address
	$hwKwhIP                = '192.168.178.2';     			 // HomeWizard Solar kWh meter IP address
	$hwEcoFlowOneIP         = '192.168.178.3';     			 // HomeWizard EcoFlow One socket IP address
	$hwEcoFlowTwoIP         = '192.168.178.4';     			 // HomeWizard EcoFlow Two socket IP address
	$hwChargerOneIP         = '192.168.178.5';     			 // HomeWizard Charger ONE (350W socket) IP address
	$hwChargerTwoIP         = '192.168.178.6';     			 // HomeWizard Charger TWO (600W socket) IP address
	$hwChargerThreeIP       = '192.168.178.7';    				 // HomeWizard Charger THREE (350W socket) IP address
	$hwChargerFourIP        = '192.168.178.8';    			 // HomeWizard Charger FOUR (300W socket) IP address
	
// = Marstek variables
	$hwMarstekIP         	= '192.168.178.100';    			 // HomeWizard Marstek socket IP address
	$hwMarstekMaxReturn 	=  800;

	$marstekIP               = '192.168.178.9';
	$marstekPort             = 30000;
	
// = Chargers
	$chargers = [
		'charger1' => ['ip' => ''.$hwChargerOneIP.'', 'power' => 350, 'label' => 'one', 'master' => true, 'spare_charger' => false],
		'charger2' => ['ip' => ''.$hwChargerTwoIP.'', 'power' => 620, 'label' => 'two', 'master' => false, 'spare_charger' => false],
		'charger3' => ['ip' => ''.$hwChargerThreeIP.'', 'power' => 350, 'label' => 'three', 'master' => false, 'spare_charger' => false],
		'charger4' => ['ip' => ''.$hwChargerFourIP.'', 'power' => 350, 'label' => 'four', 'master' => false, 'spare_charger' => false],
	];

// = Ecoflow Powerstream API variables
	$ecoflowAccessKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API access key
	$ecoflowSecretKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API secret key
	$ecoflowOneSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream One serialnummer
	$ecoflowTwoSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream Two serialnummer

// = Domoticz dummy devices variables
	$domoticzIP			    = '127.0.0.1:8080'; 	    	     // IP + poort van Domoticz
	$batterySOCIDX 		 	= '64';
	$marstekSOCIDX 		 	= '152';
	$batteryVoltageIDX 		= '41';
	$batteryAvailIDX        = '68';
	$marstekAvailIDX        = '155';
	$batteryChargeTimeIDX   = '66';
	$batteryDischargeTimeIDX= '67';
	$marstekChargeTimeIDX   = '153';
	$marstekDischargeTimeIDX= '154';
	$inputCounterIDX 	    = '60';
	$outputCounterIDX 	    = '58';
	$marstekInputCounterIDX = '160';
	$marstekOutputCounterIDX= '161';
	//$ecoFlowTempIDX 		= '50';
	$batteryRTEIDX 		    = '145';
	
// = URLs
	$baseUrl = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$urls = [	
		'batteryVoltageIDX'       => $baseUrl . $batteryVoltageIDX,	
		//'ecoFlowTempIDX'          => $baseUrl . $ecoFlowTempIDX,
		'batterySOCIDX'           => $baseUrl . $batterySOCIDX,
		'marstekSOCIDX'           => $baseUrl . $marstekSOCIDX,
		'batteryAvailIDX'         => $baseUrl . $batteryAvailIDX,
		'marstekAvailIDX'         => $baseUrl . $marstekAvailIDX,
	    'batteryChargeTimeIDX'    => $baseUrl . $batteryChargeTimeIDX,
		'batteryDischargeTimeIDX' => $baseUrl . $batteryDischargeTimeIDX,
	    'marstekChargeTimeIDX'    => $baseUrl . $marstekChargeTimeIDX,
		'marstekDischargeTimeIDX' => $baseUrl . $marstekDischargeTimeIDX,
		'outputCounterIDX'        => $baseUrl . $outputCounterIDX,
		'inputCounterIDX'         => $baseUrl . $inputCounterIDX,
		'marstekOutputCounterIDX' => $baseUrl . $marstekOutputCounterIDX,
		'marstekInputCounterIDX'  => $baseUrl . $marstekInputCounterIDX,
		'batteryRTEIDX'           => $baseUrl . $batteryRTEIDX
	];

?>
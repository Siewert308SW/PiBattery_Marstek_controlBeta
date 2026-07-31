<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                        Config variables                       //
// **************************************************************//
//                                                               //

// = -------------------------------------------------
// = System Log/Debug
// = -------------------------------------------------
	$debug                  = 'yes';        					 // Value 'yes' or 'no'
	$runtimeDebug           = 'no';         					 // Value 'yes' or 'no'

// = -------------------------------------------------
// = Location/Time variables
// = -------------------------------------------------
	$timezone               = 'Europe/Amsterdam'; 				 // My php.ini doesn't apply the timezone, so it's set manually here
	$latitude               = '53.30261';   					 // Latitude
	$longitude              = '6.60988';   					 	 // Longitude
	$zenitLat               = '89.5';       					 // Zenith latitude
	$zenitLong              = '91.7';       					 // Zenith longitude
	$sunriseOffset          = 1;								 // Hours after sunrise before injection is allowed (Winter break)
	$sunsetOffset           = 1;								 // Hours before sunset after which injection is blocked (Winter break)

// = -------------------------------------------------
// = piBattery battery variables
// = -------------------------------------------------
	$batteryVolt            = 25.6;         					 // Battery Voltage
	$batteryAh              = 300;          					 // Total Ah of all batteries
	$batteryMinimum         = 15;           					 // Minimum percentage to keep in the battery
	$batteryVoltMax         = 27.0;								 // Battery Voltage fully charged while chargers are idle
	$batteryVoltTrigger     = 25.3;								 // Battery Voltage 50%
	$batteryVoltMin         = 23.0;								 // Battery Voltage when empty

// = -------------------------------------------------
// = EcoFlow Inverter variables
// = -------------------------------------------------
	$ecoflowOneMaxOutput   	= 575;								 // EcoFlow inverter #1 max output
	$ecoflowTwoMaxOutput   	= 575;								 // EcoFlow inverter #2 max output
	$ecoflowMinOutput      	= 75;         					     // Minimum output (Watts); the inverter is allowed to deliver
	$ecoflowMaxInvTemp     	= 65;           					 // Maximum internal temperature (°C)

// = EcoFlow API
	$ecoflowAccessKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API access key
	$ecoflowSecretKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API secret key
	$ecoflowOneSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream One serialnummer
	$ecoflowTwoSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream Two serialnummer

// = -------------------------------------------------
// = piBattery chargers variables
// = -------------------------------------------------
	$chargerhyst            = 100;          					 	 // P1 hysteresis for toggling chargers
	$chargerWattsIdle       = 80;          					     // Standby Watts of all chargers when idle
	$chargerPause          	= 30;          					 	 // Delay in seconds before toggling chargers (prevents flip-flops)
	$chargerPausePct        = 90;           					 // When battery has been charged 100% till what % has it to drop before charging is allowed again
	$chargeSessions			= 4;                                 // How many charge sessions to calculate charging loss
	$chargerLossDefault     = 0.225;							 // Default charger loss fallback (used before dynamic calculation is available)
	$chargerSplitBuffer 	= 25;

// = -------------------------------------------------
// = Baseload variables
// = -------------------------------------------------
	$baseloadPosDelta		= 10;								 // Baseload update delta if p1 is importing @ injecting
	$baseloadNegDelta		= 10;								 // Baseload update delta if p1 is exporting @ injecting
	$baseloadIdleTimeout	= 180;								 // Seconds inverters stay on minimum output (idle) after injection stops
	$baseloadBuffer    		= 0;  							 	 // Some extra Watt added to baseload to buffer during the day to overcome flip/flops
	$baseloadSwingOffset  	= 150;								 // Change (W) bigger than this counts as a big swing
	$baseloadFluctWindow  	= 90;								 // Window (s) to look for repeating reversals
	$baseloadFluctTrigger 	= 4;								 // Reversals within the window before averaging starts
	$baseloadAvgRuns      	= 6;								 // Runs to average over while damping
	
// = -------------------------------------------------
// = BMS variables
// = -------------------------------------------------
	$bmsKeepAwake           = 'yes';        				     // Value 'yes' or 'no'
	$bmsWakeVoltOn  		= 22.0;  							 // BMS minimum voltage at which 1 charger will keep BMS awake
	$bmsWakeVoltOff 		= 23.5;  							 // BMS stop voltage at which 1 charger will stop charging

// = -------------------------------------------------
// = Phase protection variables
// = -------------------------------------------------
	$faseProtection         = 'yes';        				     // Value 'yes' or 'no'
	$maxFaseWatts           = 5000;         				     // If 'yes' whats the max Watts to guard, all chargers are turned off to prevent overloading
	$fase                   = 1;

// = -------------------------------------------------
// = Marstek variables
// = -------------------------------------------------
	$marstekVolt            = 51.2;								 // Marstek battery voltage
	$marstekAh              = 100;								 // Marstek battery capacity in Ah
	$marstekMinimum         = 15;								 // Minimum percentage to keep in the battery

// = Marstek Inverter
	$marstekMaxOutput      	= 800;								 // Marstek max output
	$marstekMaxInvTemp     	= 65;           					 // Maximum internal temperature (°C)

// = Marstek Charger
	$marstekChargerStep     = 50;								 // Marstek charger step size in Watt
	$marstekChargerMin      = 100;								 // Marstek minimum charge power in Watt
	$marstekChargerMax      = 2300;								 // Marstek maximum charge power in Watt

// = -------------------------------------------------
// = Network variables
// = -------------------------------------------------
	$hwP1IP                 = '192.168.178.77';     			 // HomeWizard P1-meter
	$hwKwhIP                = '192.168.178.83';     			 // HomeWizard Solar kWh meter
	$hwEcoFlowOneIP         = '192.168.178.88';     			 // HomeWizard EcoFlow One socket
	$hwEcoFlowTwoIP         = '192.168.178.98';     			 // HomeWizard EcoFlow Two socket
	$hwChargerOneIP         = '192.168.178.90';     			 // HomeWizard Charger ONE
	$hwChargerTwoIP         = '192.168.178.89';     			 // HomeWizard Charger TWO
	$hwChargerThreeIP       = '192.168.178.91';    				 // HomeWizard Charger THREE
	$hwChargerFourIP        = '192.168.178.103';    			 // HomeWizard Charger FOUR
	$hwMarstekIP         	= '192.168.178.100';    			 // HomeWizard Marstek socket
	$marstekIP              = '192.168.178.105';				 // Marstek ModBus

// = -------------------------------------------------
// = piBattery Chargers config
// = -------------------------------------------------
	$chargers = [
		'charger1' => ['ip' => $hwChargerOneIP,  'power' => 350, 'label' => 'one',   'master' => true,  'spare_charger' => false],
		'charger2' => ['ip' => $hwChargerTwoIP,  'power' => 700, 'label' => 'two',   'master' => false, 'spare_charger' => false],
		'charger3' => ['ip' => $hwChargerThreeIP,'power' => 350, 'label' => 'three', 'master' => false, 'spare_charger' => false],
		'charger4' => ['ip' => $hwChargerFourIP, 'power' => 350, 'label' => 'four',  'master' => false, 'spare_charger' => false],
	];

// = -------------------------------------------------
// = Domoticz variables
// = -------------------------------------------------
	$domoticzIP         = '127.0.0.1:8080';          		     // Lokale Domoticz
	$domoticzRemoteIP   = '192.168.178.7:8080';       			 // Remote Domoticz

	$domoticzIDX = [
		'batterySOC'           => ['64',   'udevice'],
		'marstekSOC'           => ['152',  'udevice'],
		'batteryVoltage'       => ['41',   'udevice'],
		'batteryAvail'         => ['68',   'udevice_text'],
		'marstekAvail'         => ['155',  'udevice_text'],
		'batteryChargeTime'    => ['66',   'udevice_text'],
		'batteryDischargeTime' => ['67',   'udevice_text'],
		'marstekChargeTime'    => ['153',  'udevice_text'],
		'marstekDischargeTime' => ['154',  'udevice_text'],
		'inputCounter'         => ['60',   'udevice'],
		'outputCounter'        => ['58',   'udevice'],
		'marstekInputCounter'  => ['160',  'udevice'],
		'marstekOutputCounter' => ['161',  'udevice'],
		'ecoFlowTemp'          => ['50',   'udevice'],
		'marstekTemp'          => ['163',  'udevice'],
		'batteryRTE'           => ['145',  'udevice'],
		'marstekRTE'           => ['162',  'udevice'],
		'aircoLiving'          => ['6162', 'udevice'],
		'aircoHoven'           => ['6173', 'udevice'],
	];

?>

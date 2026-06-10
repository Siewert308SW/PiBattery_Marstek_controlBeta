<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                  Bootstrap Require Sequence                   //
// **************************************************************//
//                                                               //

// = 1. Config
	require_once __DIR__ . '/../config/config.php';
	usleep(100000);
// = 2. API Class
	require_once __DIR__ . '/../class/ecoflow_api_class.php';
	usleep(100000);
// = 3. Marstek ModBus Class
	require_once __DIR__ . '/../class/marstek_modbus_class.php';
	usleep(100000);	
// = 4. Functions
	require_once __DIR__ . '/../includes/functions.php';
	usleep(100000);
// = 5. Variables
	require_once __DIR__ . '/../includes/variables.php';
	usleep(100000);
// = 6. Helpers
	require_once __DIR__ . '/../includes/helpers.php';
	usleep(100000);
?>
<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                  Bootstrap Require Sequence                   //
// **************************************************************//
//                                                               //

// = 1. Config
	require_once __DIR__ . '/../config/config.php';

// = 2. API Class
	require_once __DIR__ . '/../class/ecoflow_api_class.php';

// = 3. Marstek ModBus Class
	require_once __DIR__ . '/../class/marstek_modbus_class.php';
	sleep(1);
	
// = 4. Functions
	require_once __DIR__ . '/../includes/functions.php';

// = 5. Variables
	require_once __DIR__ . '/../includes/variables.php';

// = 6. Helpers
	require_once __DIR__ . '/../includes/helpers.php';

?>
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
	require_once __DIR__ . '/../includes/ecoflow_api_class.php';
	
// = 2. Functions
	require_once __DIR__ . '/../includes/functions.php';

// = 3. Variables
	require_once __DIR__ . '/../includes/variables.php';

// = 4. Helpers
	require_once __DIR__ . '/../includes/helpers.php';

?>
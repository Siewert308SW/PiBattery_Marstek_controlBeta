<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Variables                           //
// **************************************************************//
//                                                               //

// = Get Ecoflow data
	$ecoflow 				= new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$invOne 				= $ecoflow->getDevice($ecoflowOneSerialNumber);
	$invTwo 				= $ecoflow->getDevice($ecoflowTwoSerialNumber);
	
// = php.ini
	date_default_timezone_set(''.$timezone.'');
	
// = Time/Date now
	$currentTimestamp 		= time();
	$currentTime 			= date('H:i');
	$dateNow 				= date('Y-m-d H:i:s');
	$dateTime 				= new DateTime(''.$dateNow.'', new DateTimeZone(''.$timezone.''));
	$isWinter 				= ($dateTime->format('n') < 3 || $dateTime->format('n') >= 10);
	
// = Check DST time
	$isDST = $dateTime->format("I");
	if ($isDST == '1'){
	$gmt = '1';
	} else {
	$gmt = '0';
	}

// = Get Sunrise/Sunset
	$sunrise 				= (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset 				= (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));

// = Adjust Sunrise/Sunset
	$sunriseTime 			= DateTime::createFromFormat('H:i', $sunrise);
	$sunsetTime 			= DateTime::createFromFormat('H:i', $sunset);
	
	$sunriseTime->modify('+1 hour');
	$sunsetTime->modify('-1 hour');
	
	$sunriseLate	 		= $sunriseTime->format('H:i');
	$sunsetEarly		 	= $sunsetTime->format('H:i');
	
// = Get current variable files
	$varsFile               = $piBatteryPath . 'data/variables.json';
	$vars                   = file_exists($varsFile) ? json_decode(file_get_contents($varsFile), true) : [];

// = Lock files
	$chargerLockFile        = $piBatteryPath . 'data/chargerLocked.json';
	$chargerLockFileVars    = file_exists($chargerLockFile) ? json_decode(file_get_contents($chargerLockFile), true) : [];
	$chargerLock 	  		= $chargerLockFileVars['chargerLocked'] ?? false;
	
// = Marstek Get Variables
	$marstekvarsFile        = $piBatteryPath . 'data/marstek_state.json';
	$marstekvars            = file_exists($marstekvarsFile) ? json_decode(file_get_contents($marstekvarsFile), true) : [];

// = Marstek Variables
	$marstek_Modus 	    	= $vars['marstek_Modus'] ?? null;
	$marstek_BatModus 	    = $vars['marstek_Modus'] ?? null;
	$marstek_Runtime 	    = $vars['marstek_Runtime'] ?? null;		
	$marstekMode  			= $marstekvars['marstekMode'] ?? false;
	$marstekBatMode  		= $marstekvars['marstekMode'] ?? false;
	$marstekState 			= $marstekvars['marstekState'] ?? false;
	$marstekBatState 		= $marstekvars['marstekState'] ?? false;
	$marstekSoc   			= $marstekvars['marstekSoc'] ?? $marstekMinimum;
	$marstekBatSoc   	    = $marstekvars['marstekSoc'] ?? $marstekMinimum;
	
	$hwMarstekSocket = getHwData($hwMarstekIP);
	if ($hwMarstekSocket >= 0 && $hwMarstekSocket <= 10) {
		$hwMarstekReturn = 0; 
		$hwMarstekUsage = 0;
	} elseif ($hwMarstekSocket > 10) {
		$hwMarstekReturn = 0; 
		$hwMarstekUsage = $hwMarstekSocket;		
	} elseif ($hwMarstekSocket < 0) {
		$hwMarstekReturn = $hwMarstekSocket; 
		$hwMarstekUsage = 0;		
	}
	
// = HomeWizard GET Variables
	$hwP1Usage              = getHwData($hwP1IP);
	$hwP1Fase               = getHwP1FaseData($hwP1IP, $fase);
	$hwP1Fase2              = getHwP1FaseData($hwP1IP, $fase2);
	$hwP1Fase3              = getHwP1FaseData($hwP1IP, $fase3);
	$hwSolarReturn          = getHwData($hwKwhIP);
	$hwInvOneReturn         = getHwData($hwEcoFlowOneIP);
	$hwInvTwoReturn         = getHwData($hwEcoFlowTwoIP);
	$hwInvsReturn           = ($hwInvOneReturn + $hwInvTwoReturn);
	$hwInvReturn            = ($hwInvOneReturn + $hwInvTwoReturn + $hwMarstekReturn);

	$hwChargerOneUsage      = getHwData($hwChargerOneIP);
	$hwChargerTwoUsage      = getHwData($hwChargerTwoIP);
	$hwChargerThreeUsage    = getHwData($hwChargerThreeIP);
	$hwChargerFourUsage    	= getHwData($hwChargerFourIP);
	$hwChargersUsage        = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage);
	$hwChargerUsage         = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage + $hwMarstekUsage);

	$hwChargerOneStatus     = getHwStatus($hwChargerOneIP);
	$hwChargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	$hwChargerThreeStatus   = getHwStatus($hwChargerThreeIP);
	$hwChargerFourStatus    = getHwStatus($hwChargerFourIP);
	$hwMarstekStatus        = getHwStatus($hwMarstekIP);
	
	$hwInvOneStatus         = getHwStatus($hwEcoFlowOneIP);
	$hwInvTwoStatus         = getHwStatus($hwEcoFlowTwoIP);
	
// = Get battery Voltage via inverter
	$pv1OneInputVolt 		= ($invOne['data']['20_1.pv1InputVolt']) / 10;
	$pv2OneInputVolt 		= ($invOne['data']['20_1.pv2InputVolt']) / 10;
	$pvAvOneInputVoltage    = round(($pv1OneInputVolt + $pv2OneInputVolt) / 2, 2);

	$pv1TwoInputVolt 		= ($invTwo['data']['20_1.pv1InputVolt']) / 10;
	$pv2TwoInputVolt 		= ($invTwo['data']['20_1.pv2InputVolt']) / 10;
	$pvAvTwoInputVoltage    = round(($pv1TwoInputVolt + $pv2TwoInputVolt) / 2, 2);
	
	$pvAvInputVoltage       = round(($pvAvOneInputVoltage + $pvAvTwoInputVoltage) / 2, 2);

// = Get Inverter status
	//$invOneStatus			= $ecoflow->getDeviceOnline($ecoflowOneSerialNumber);
	//$invTwoStatus			= $ecoflow->getDeviceOnline($ecoflowTwoSerialNumber);
	$invOneTemp             = ($invOne['data']['20_1.llcTemp']) / 10;
	$invTwoTemp             = ($invTwo['data']['20_1.llcTemp']) / 10;
	$invTemp                = ($invOneTemp + $invTwoTemp) / 2;
	
// = Get P1 / Solar and real power usage
	$productionTotal        = ($hwSolarReturn + $hwInvReturn);	
	$realUsage              = ($hwP1Usage - $productionTotal);
	$P1ChargerUsage         = ($hwP1Usage - $hwChargerUsage);
	$P1ChargerRef     		= ($P1ChargerUsage + $hwChargerUsage);
	$P1ChargerAvailable     = ($P1ChargerRef < 0 && $hwInvReturn == 0 ? $P1ChargerRef : 0);
	
// = Get Inverter and charger real output
	$hwInvOneTotal          = getHwTotalOutputData($hwEcoFlowOneIP);
	$hwInvTwoTotal          = getHwTotalOutputData($hwEcoFlowTwoIP);
	$hwInvTotal             = ($hwInvOneTotal + $hwInvTwoTotal);
	$hwChargerOneTotal      = getHwTotalInputData($hwChargerOneIP);
	$hwChargerTwoTotal      = getHwTotalInputData($hwChargerTwoIP);
	$hwChargerThreeTotal    = getHwTotalInputData($hwChargerThreeIP);
	$hwChargerFourTotal     = getHwTotalInputData($hwChargerFourIP);
	$hwChargersTotalInput   = ($hwChargerOneTotal + $hwChargerTwoTotal + $hwChargerThreeTotal + $hwChargerFourTotal);

// = Get Current Baseload
	$totalMaxOutput        	= ($ecoflowOneMaxOutput + $ecoflowTwoMaxOutput + $marstekMaxOutput);
	$currentOneBaseload	    = ($invOne['data']['20_1.permanentWatts']) / 10;
	$currentTwoBaseload	    = ($invTwo['data']['20_1.permanentWatts']) / 10;
	$currentThreeBaseload	= abs($hwMarstekReturn);	
	$currentBaseload	    = ($currentOneBaseload + $currentTwoBaseload + $currentThreeBaseload);
	$oldBaseload 			= $vars['oldBaseload'] ?? 0;
	
// = Various
	$pauseUntil       		= $vars['charger_pause_until'] ?? 0;
	$pendingSwitch 	  		= $vars['charger_pending_switch'] ?? false;
	$charger_pending_type 	= $vars['charger_pending_type'] ?? null;
	$chargerLoss 			= round($vars['charger_loss_dynamic'] ?? 0.22524337035732608, 7);	
	$pauseCharging 			= $vars['pauseCharging'] ?? false;
	$pauseMarstekCharging   = $vars['pauseMarstekCharging'] ?? false;
	$keepChargersOff 		= $vars['keepChargersOff'] ?? false;
	$faseProtect	 		= $vars['faseProtect'] ?? false;
	$chargeLossCalculation 	= $vars['charge_loss_calculation'] ?? false;
	$pendingCharging		= $vars['charger_pending_switch'] ?? false;
	$battery_calibrated		= $vars['battery_calibrated'] ?? false;
	$bmsWakeActive  		= $vars['bmsWakeActive'] ?? false;
	$battery_empty			= $vars['battery_empty'] ?? false;
	$battery_allowed		= $vars['battery_allowed'] ?? false;
	$invInjection			= $vars['invInjection'] ?? false;
	$testVarUpdateNeeded	= $vars['testVarUpdateNeeded'] ?? false;
	$testVarUpdateNotNeeded = $vars['testVarUpdateNotNeeded'] ?? false;
	
// = Get/Set Battery Charge/Discharge/SOC values
	$batteryCapacitykWh     = ($batteryVolt * $batteryAh / 1000);
	$batteryCapacityWh 		= ($batteryCapacitykWh * 1000);
	
	$chargeStart	 		= round($vars['charge_session']['chargeStart'], 3);
	$chargeCalibrated		= round($vars['charge_session']['chargeCalibrated'], 3);
	$chargeEnd	 			= round($hwChargersTotalInput, 3);
	
	$dischargeStart	 		= round($vars['charge_session']['dischargeStart'], 3);
	$dischargeEnd	 		= round($hwInvTotal, 3);

	$brutoCharged			= round(($chargeEnd - $chargeStart), 3);
	$nettoCharged			= round(($chargeEnd - $chargeCalibrated), 3);
	$brutoDischarged 		= round(($dischargeEnd - $dischargeStart), 3);
	$batteryAvailable	    = round((($batteryCapacitykWh) - ($brutoDischarged - ($brutoCharged  * (1 - $chargerLoss)))), 2);
	$marstekCapacitykWh	    = round((51.2 * 100 / 1000), 2);
	$marstekCapacityWh 		= ($marstekCapacitykWh * 1000);
	$marstekAvailable	    = round(($marstekCapacitykWh / 100 * $marstekBatSoc), 2);
	$batteryPct 			= round(($batteryAvailable / $batteryCapacitykWh) * 100, 0);

	$battery_emptyTime 		= $vars['battery_empty_time'] ?? time();
	$hoursSinceEmpty 		= round((time() - $battery_emptyTime) / 3600, 1);	

	$battery_bmsWake_time 	= $vars['battery_bmsWake_time'] ?? time();
	$hoursSince_Wake_time   = round((time() - $battery_bmsWake_time) / 3600, 1);

	$mayDischarge 	    	= $vars['battery_allowed'] ?? false;

	$totalCapacitykWh       = ($batteryCapacitykWh + $marstekCapacitykWh);

// = Determine which battery is active for injection 
	$usePiBattery 			= !($pvAvInputVoltage < $batteryVoltMin || $batteryPct <= $batteryMinimum || isset($vars['piBattery_empty']));
	$useMarstek   			= ($marstekBatSoc > $marstekMinimum && !isset($vars['marstek_empty']));
	
	$ecoflowOneMax   		= ($ecoflowOneMaxOutput * 10);
	$ecoflowTwoMax   		= ($ecoflowTwoMaxOutput * 10);
	$marstekMax      		= ($marstekMaxOutput * 10);
	
// = Get status for all chargers
	foreach ($chargers as $name => &$data) {
		$data['status'] = getHwStatus($data['ip']);
	}
	unset($data);
?>
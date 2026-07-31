<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Variables                           //
// **************************************************************//
//                                                               //

// = php.ini
	date_default_timezone_set(''.$timezone.'');
	
// = Get Ecoflow data
	$ecoflow 				= new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$invOne 				= $ecoflow->getDevice($ecoflowOneSerialNumber);
	$invTwo 				= $ecoflow->getDevice($ecoflowTwoSerialNumber);

// = Get Marstek V3 data
	$marstek 				= new MarstekModbus($marstekIP);
	$marstekData 			= $marstek->getData();
	
// = Time/Date now
	$currentTimestamp 		= time();
	$currentTime 			= date('H:i');
	$dateNow 				= date('Y-m-d H:i:s');
	$dateTime 				= new DateTime(''.$dateNow.'', new DateTimeZone(''.$timezone.''));
	$isWinter 				= ($dateTime->format('n') < 3 || $dateTime->format('n') >= 10);
	
// = Check DST time
	$isDST = $dateTime->format("I");
	if ($isDST == '1'){
	$gmt = '2';
	} else {
	$gmt = '1';
	}

// = Get Sunrise/Sunset
	$sunrise 				= (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset 				= (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));

// = Adjust Sunrise/Sunset
	$sunriseTime 			= DateTime::createFromFormat('H:i', $sunrise);
	$sunsetTime 			= DateTime::createFromFormat('H:i', $sunset);
	
	$sunriseTime->modify('+' . $sunriseOffset . ' hour');
	$sunsetTime->modify('-' . $sunsetOffset . ' hour');
	
	$sunriseLate	 		= $sunriseTime->format('H:i');
	$sunsetEarly		 	= $sunsetTime->format('H:i');

	$dayTime				= ($currentTime >= $sunriseLate && $currentTime <= $sunsetEarly);
	
// = Get current variable files
	$varsFile               = $piBatteryPath . 'data/variables.json';
	$vars                   = file_exists($varsFile) ? json_decode(file_get_contents($varsFile), true) : [];

// = Domoticz State File
	$domoticzStateFile 		= $piBatteryPath . 'data/domoticz_state.json';

// = Marstek Variables
	$marstekStateFile		= $piBatteryPath . 'data/marstek_state.json';

	if ($marstekData['online']) {
		writeJsonLocked($marstekStateFile, $marstekData);
	} else {
		$marstekCache = file_exists($marstekStateFile) ? json_decode(file_get_contents($marstekStateFile), true) : [];
		$marstekData  = array_merge($marstekCache, $marstekData);
	}

	$marstekVoltage			= $marstekData['batteryVoltage'] ?? 0;
	$marstekState 			= $marstekData['inverterState'] ?? 0;
	$marstekSoc   	   		= $marstekData['batterySoc'] ?? 0;
	$marstekAcPower		    = $marstekData['acPower'] ?? 0;
	$marstekTemp		    = $marstekData['batteryTemp'] ?? 0;
	$marstekRTE		    	= $marstekData['lifetimeRte'] ?? 0;
	
	$hwMarstekSocket = getHwData($hwMarstekIP);
	if ($hwMarstekSocket >= 0 && $hwMarstekSocket < 11) {
		$hwMarstekReturn = 0; 
		$hwMarstekUsage = 0;
	} elseif ($hwMarstekSocket > 11) {
		$hwMarstekReturn = 0; 
		$hwMarstekUsage = $hwMarstekSocket;		
	} elseif ($hwMarstekSocket < 0) {
		$hwMarstekReturn = $hwMarstekSocket; 
		$hwMarstekUsage = 0;		
	}
	
// = HomeWizard GET Variables
	$hwP1Usage              = getHwData($hwP1IP);
	$hwP1Fase               = getHwP1FaseData($hwP1IP, $fase);
	$hwSolarReturn          = getHwData($hwKwhIP);
	$hw_InvOne              = getHwAll($hwEcoFlowOneIP);
	$hw_InvTwo              = getHwAll($hwEcoFlowTwoIP);
	$hw_ChargerOne          = getHwAll($hwChargerOneIP);
	$hw_ChargerTwo          = getHwAll($hwChargerTwoIP);
	$hw_ChargerThree        = getHwAll($hwChargerThreeIP);
	$hw_ChargerFour         = getHwAll($hwChargerFourIP);

	$hwInvOneReturn         = $hw_InvOne['power'];
	$hwInvTwoReturn         = $hw_InvTwo['power'];
	$hwInvsReturn           = ($hwInvOneReturn + $hwInvTwoReturn);
	$hwInvReturn            = ($hwInvOneReturn + $hwInvTwoReturn + $hwMarstekReturn);

	$hwChargerOneUsage      = $hw_ChargerOne['power'];
	$hwChargerTwoUsage      = $hw_ChargerTwo['power'];
	$hwChargerThreeUsage    = $hw_ChargerThree['power'];
	$hwChargerFourUsage    	= $hw_ChargerFour['power'];
	$hwChargersUsage        = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage);
	$hwChargerUsage         = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage + $hwMarstekUsage);

	$hwChargerOneStatus     = $hw_ChargerOne['status'];
	$hwChargerTwoStatus     = $hw_ChargerTwo['status'];
	$hwChargerThreeStatus   = $hw_ChargerThree['status'];
	$hwChargerFourStatus    = $hw_ChargerFour['status'];
	$hwMarstekStatus        = getHwStatus($hwMarstekIP);
	
	$hwInvOneStatus         = $hw_InvOne['status'];
	$hwInvTwoStatus         = $hw_InvTwo['status'];
	
// = Get battery Voltage via inverter
	$pv1OneInputVolt 		= ($invOne['data']['20_1.pv1InputVolt']) / 10;
	$pv2OneInputVolt 		= ($invOne['data']['20_1.pv2InputVolt']) / 10;
	$pvAvOneInputVoltage    = round(($pv1OneInputVolt + $pv2OneInputVolt) / 2, 2);

	$pv1TwoInputVolt 		= ($invTwo['data']['20_1.pv1InputVolt']) / 10;
	$pv2TwoInputVolt 		= ($invTwo['data']['20_1.pv2InputVolt']) / 10;
	$pvAvTwoInputVoltage    = round(($pv1TwoInputVolt + $pv2TwoInputVolt) / 2, 2);
	
	$pvAvInputVoltage       = round(($pvAvOneInputVoltage + $pvAvTwoInputVoltage) / 2, 1);

// = Get Inverter status
	//$invOneStatus			= $ecoflow->getDeviceOnline($ecoflowOneSerialNumber);
	//$invTwoStatus			= $ecoflow->getDeviceOnline($ecoflowTwoSerialNumber);
	$invOneTemp             = ($invOne['data']['20_1.llcTemp']) / 10;
	$invTwoTemp             = ($invTwo['data']['20_1.llcTemp']) / 10;
	$invTemp                = round(($invOneTemp + $invTwoTemp) / 2, 0);
	
// = Get P1 / Solar and real power usage
	$productionTotal        = ($hwSolarReturn + $hwInvReturn);	
	$realUsage              = ($hwP1Usage - $productionTotal);
	$P1ChargerUsage         = ($hwP1Usage - $hwChargerUsage);
	
// = Get Inverter and charger real output
	$hwInvOneTotal          = $hw_InvOne['total_export'];
	$hwInvTwoTotal          = $hw_InvTwo['total_export'];
	$hwInvTotal             = ($hwInvOneTotal + $hwInvTwoTotal);
	$hwChargerOneTotal      = $hw_ChargerOne['total_import'];
	$hwChargerTwoTotal      = $hw_ChargerTwo['total_import'];
	$hwChargerThreeTotal    = $hw_ChargerThree['total_import'];
	$hwChargerFourTotal     = $hw_ChargerFour['total_import'];
	$hwChargersTotalInput   = ($hwChargerOneTotal + $hwChargerTwoTotal + $hwChargerThreeTotal + $hwChargerFourTotal);

// = Get Current Baseload
	$totalMaxOutput        	= ($ecoflowOneMaxOutput + $ecoflowTwoMaxOutput + $marstekMaxOutput);
	$currentOneBaseload	    = ($invOne['data']['20_1.permanentWatts']) / 10;
	$currentTwoBaseload	    = ($invTwo['data']['20_1.permanentWatts']) / 10;
	$currentThreeBaseload	= abs($hwMarstekReturn);	
	$currentBaseload	    = ($currentOneBaseload + $currentTwoBaseload + $currentThreeBaseload);
	$oldBaseload 			= $vars['oldBaseload'] ?? 0;
	
// = Various
	$systemFailure			= $vars['systemFailure'] ?? false;
	$systemFailureIssue 	= $vars['systemFailureIssue'] ?? null;
	$pauseUntil       		= $vars['charger_pause_until'] ?? 0;
	$pendingSwitch 	  		= $vars['charger_pending_switch'] ?? false;
	$charger_pending_type 	= $vars['charger_pending_type'] ?? null;
	$chargerLoss 			= round($vars['charger_loss_dynamic'] ?? $chargerLossDefault, 7);
	$chargerRTE 			= round(100 - ($chargerLoss * 100), 1);	
	$pauseCharging 			= $vars['pauseCharging'] ?? false;
	$pauseMarstekCharging   = $vars['pauseMarstekCharging'] ?? false;
	$keepChargersOff 		= $vars['keepChargersOff'] ?? false;
	$faseProtect	 		= $vars['faseProtect'] ?? false;
	$battery_calibrated		= $vars['battery_calibrated'] ?? false;
	$bmsWakeActive  		= $vars['bmsWakeActive'] ?? false;
	$invInjection			= $vars['invInjection'] ?? false;
	$baseloadIdle			= $vars['baseloadIdle'] ?? false;
	$baseloadIdleUntil		= $vars['baseload_idle_until'] ?? 0;
	$battery_awaitingCalibration = $vars['battery_awaitingCalibration'] ?? false;
	$sunScore				= $vars['sunScore'] ?? 0;
	
// = Get/Set Battery Charge/Discharge/SOC values
	$batteryCapacitykWh     = ($batteryVolt * $batteryAh / 1000);
	$batteryCapacityWh 		= ($batteryCapacitykWh * 1000);
	
	$chargeStart	 		= round($vars['charge_session']['chargeStart'], 3);
	$chargeCalibrated		= round($vars['charge_session']['chargeCalibrated'], 3);
	$chargeEnd	 			= round($hwChargersTotalInput, 3);
	
	$dischargeStart	 		= round($vars['charge_session']['dischargeStart'], 3);
	$dischargeEnd	 		= round($hwInvTotal, 3);

	$brutoCharged			= round(($chargeEnd - $chargeStart), 3);
	$brutoDischarged 		= round(($dischargeEnd - $dischargeStart), 3);
	$batteryAvailable	    = round((($batteryCapacitykWh) - ($brutoDischarged - ($brutoCharged  * (1 - $chargerLoss)))), 2);
	$marstekCapacitykWh	    = round(($marstekVolt * $marstekAh / 1000), 2);
	$marstekCapacityWh 		= ($marstekCapacitykWh * 1000);
	$marstekAvailable	    = round(($marstekCapacitykWh / 100 * $marstekSoc), 2);
	$batteryPct 			= round(($batteryAvailable / $batteryCapacitykWh) * 100, 0);

	$battery_emptyTime 		= $vars['battery_empty_time'] ?? time();
	$battery_bmsWake_time 	= $vars['battery_bmsWake_time'] ?? time();
	$hoursSince_Wake_time   = round((time() - $battery_bmsWake_time) / 3600, 1);
	$totalCapacitykWh       = ($batteryCapacitykWh + $marstekCapacitykWh);

// = Determine which battery is active for injection 
	$usePiBattery 			= !($pvAvInputVoltage < $batteryVoltMin || $batteryPct <= $batteryMinimum || isset($vars['piBattery_empty']) && $hwInvOneStatus == 'On' && $hwInvTwoStatus == 'On');
	$useMarstek   			= ($marstekSoc > $marstekMinimum && !isset($vars['marstek_empty']) && $hwMarstekStatus == 'On');
	
	$ecoflowOneMax   		= ($ecoflowOneMaxOutput * 10);
	$ecoflowTwoMax   		= ($ecoflowTwoMaxOutput * 10);
	$marstekMax      		= ($marstekMaxOutput * 10);
?>
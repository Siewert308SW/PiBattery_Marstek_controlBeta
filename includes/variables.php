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

// = Get Marstek V3 data
	$marstek 				= new MarstekModbus($marstekIP);
	$marstekData 			= $marstek->getData();
	
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
	
	$sunriseTime->modify('+' . $sunriseOffset . ' hour');
	$sunsetTime->modify('-' . $sunsetOffset . ' hour');
	
	$sunriseLate	 		= $sunriseTime->format('H:i');
	$sunsetEarly		 	= $sunsetTime->format('H:i');
	
// = Get current variable files
	$varsFile               = $piBatteryPath . 'data/variables.json';
	$vars                   = file_exists($varsFile) ? json_decode(file_get_contents($varsFile), true) : [];

// = Lock files
	$chargerLockFile        = $piBatteryPath . 'data/chargerLocked.json';
	$chargerLockFileVars    = file_exists($chargerLockFile) ? json_decode(file_get_contents($chargerLockFile), true) : [];
	$chargerLock 	  		= $chargerLockFileVars['chargerLocked'] ?? false;

// = Domoticz State File
	$domoticzStateFile 		= $piBatteryPath . 'data/domoticz_state.json';

// = Get solar surplus history
	$surplusHistoryFile 	= $piBatteryPath . 'data/surplusHistory.json';
	$varsHist 				= file_exists($surplusHistoryFile) ? json_decode(file_get_contents($surplusHistoryFile), true) : [];
	
// = Marstek Variables
	$marstekBatVolt 		= $marstekData['batteryVoltage'];
	$marstekBatState 		= $marstekData['inverterState'];
	$marstekBatSoc   	    = $marstekData['batterySoc'];
	$marstekAcPower		    = $marstekData['acPower'];
	$marstekState		    = $marstekData['inverterState'];
	$marstekTemp		    = $marstekData['batteryTemp'];
	$marstekRTE		    	= $marstekData['lifetimeRte'];
	
	$hwMarstekSocket = getHwData($hwMarstekIP);
	if ($hwMarstekSocket >= 0 && $hwMarstekSocket <= $marstekSocketThreshold) {
		$hwMarstekReturn = 0; 
		$hwMarstekUsage = 0;
	} elseif ($hwMarstekSocket > $marstekSocketThreshold) {
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
	$_hwInvOne              = getHwAll($hwEcoFlowOneIP);
	$_hwInvTwo              = getHwAll($hwEcoFlowTwoIP);
	$_hwChargerOne          = getHwAll($hwChargerOneIP);
	$_hwChargerTwo          = getHwAll($hwChargerTwoIP);
	$_hwChargerThree        = getHwAll($hwChargerThreeIP);
	$_hwChargerFour         = getHwAll($hwChargerFourIP);

	$hwInvOneReturn         = $_hwInvOne['power'];
	$hwInvTwoReturn         = $_hwInvTwo['power'];
	$hwInvsReturn           = ($hwInvOneReturn + $hwInvTwoReturn);
	$hwInvReturn            = ($hwInvOneReturn + $hwInvTwoReturn + $hwMarstekReturn);

	$hwChargerOneUsage      = $_hwChargerOne['power'];
	$hwChargerTwoUsage      = $_hwChargerTwo['power'];
	$hwChargerThreeUsage    = $_hwChargerThree['power'];
	$hwChargerFourUsage    	= $_hwChargerFour['power'];
	$hwChargersUsage        = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage);
	$hwChargerUsage         = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage + $hwChargerFourUsage + $hwMarstekUsage);

	$hwChargerOneStatus     = $_hwChargerOne['status'];
	$hwChargerTwoStatus     = $_hwChargerTwo['status'];
	$hwChargerThreeStatus   = $_hwChargerThree['status'];
	$hwChargerFourStatus    = $_hwChargerFour['status'];
	$hwMarstekStatus        = getHwStatus($hwMarstekIP);
	
	$hwInvOneStatus         = $_hwInvOne['status'];
	$hwInvTwoStatus         = $_hwInvTwo['status'];
	
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
	$P1ChargerRef     		= ($P1ChargerUsage + $hwChargerUsage);
	$P1ChargerAvailable     = ($P1ChargerRef < 0 && $hwInvReturn == 0 ? $P1ChargerRef : 0);
	
// = Get Inverter and charger real output
	$hwInvOneTotal          = $_hwInvOne['total_export'];
	$hwInvTwoTotal          = $_hwInvTwo['total_export'];
	$hwInvTotal             = ($hwInvOneTotal + $hwInvTwoTotal);
	$hwChargerOneTotal      = $_hwChargerOne['total_import'];
	$hwChargerTwoTotal      = $_hwChargerTwo['total_import'];
	$hwChargerThreeTotal    = $_hwChargerThree['total_import'];
	$hwChargerFourTotal     = $_hwChargerFour['total_import'];
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
	$baseloadPendingSwitch 	= $vars['baseloading_pending_switch'] ?? false;
	$charger_pending_type 	= $vars['charger_pending_type'] ?? null;
	$chargerLoss 			= round($vars['charger_loss_dynamic'] ?? $chargerLossDefault, 7);
	$chargerRTE 			= round(100 - ($chargerLoss * 100), 1);	
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
	$baseloadIdleUntil		= $vars['baseload_idle_until'] ?? 0;
	
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
	$marstekCapacitykWh	    = round(($marstekVolt * $marstekAh / 1000), 2);
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
?>
<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Helpers                             //
// **************************************************************//
//

// -------------------------------------------------
// Marstek Set Mode
// -------------------------------------------------

// === Marstek fully charged
	if ($runMarstek && !$isManualRun && $marstekBatSoc >= 100 && $marstekBatMode == 'Auto' && $marstekBatState == 'idle'){
		//if ($marstek_Modus != 'Ready' || $marstekBatMode == 'Auto') {
			setMarstekMode('stop');
			$vars['marstek_Modus'] = 'Ready';
			$varsChanged = true;
		//}
	}
// === Marstek discharged during evening/nighttime
	if ($runMarstek && !$isManualRun && $marstekBatSoc <= 16 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'Empty' && $marstekBatState == 'idle'){
			setMarstekMode('stop');
			$vars['marstek_Modus'] = 'Empty';
			$varsChanged = true;
	}

// === piBattery discharged
	if ($runMarstek && !$isManualRun && $marstekBatSoc > 16 && $marstekBatSoc < 90 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'EmptyPi' && isset($vars['battery_empty']) && $hwSolarReturn == 0){
			setMarstekMode('stop');
			$vars['marstek_Modus'] = 'EmptyPi';
			$varsChanged = true;
	}

// === Marstek auto chargemode
	if ($runMarstek && !$isManualRun && $marstekBatSoc > 16 && $marstekBatSoc < 90 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'Empty' && !isset($vars['battery_empty']) && $hwSolarReturn <= -300 && $keepChargersOff == true){
			setMarstekMode('stop');
			$vars['marstek_Modus'] = 'Empty';
			$varsChanged = true;
	}
	
// === Marstek AUTO mode during daytime
	if ($runMarstek && !$isManualRun && $marstekBatMode != 'Auto' && $marstek_BatModus == 'Empty' && $marstekBatState == 'idle' && $hwSolarReturn <= -300){
			setMarstekMode('auto');
	} elseif ($runMarstek && !$isManualRun && $marstekBatMode != 'Auto' && $marstek_BatModus == 'EmptyPi'){
			setMarstekMode('auto');
	}
	
// -------------------------------------------------
// Reset $vars['battery_empty'] or $vars['battery_empty_volt']
// -------------------------------------------------
	//if ($runBaseload && !$isManualRun){
	//	if ($batteryPct > 99 && $pvAvInputVoltage >= $batteryVoltMax && isset($vars['battery_empty'])) {
						
	//		unset($vars['battery_empty']);
	//		$varsChanged = true;
	//	}
	//}
	
// = -------------------------------------------------	
// = EcoFlow Fan On/Off 
// = -------------------------------------------------
	//if ($runCharger && !$isManualRun){
	//	if ($hwInvFanStatus == 'Off' && $invTemp >= 35){
	//		switchHwSocket('fan','On');
	//	} elseif ($hwInvFanStatus == 'On' && $invTemp < 30){
	//		switchHwSocket('fan','Off');
	//	}
	//}
	
// = -------------------------------------------------	
// = Estimated charge/discharge time  || $runCharger
// = -------------------------------------------------

	if ($runBaseload || $isManualRun){
// === ChargeTime till 100%
		if ($hwChargersUsage > $chargerWattsIdle && $batteryPct < 100) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$neededWh = $batteryCapacityWh - $currentWh;
			$neededWhAdjusted = $neededWh / (1 - $chargerLoss);
			$chargeTime = $neededWhAdjusted / $hwChargersUsage;
			$realChargeTime = convertTime($chargeTime);
			
		}

// === DischargeTime till minimum-SOC
		if ($hwInvsReturn != 0 && $batteryPct > $batteryMinimum) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$minWh = ($batteryMinimum / 100) * $batteryCapacityWh;
			$availableWh = $currentWh - $minWh;
			$dischargeTime = $availableWh / abs($hwInvsReturn);
			$realDischargeTime = convertTime($dischargeTime);
		}

// === Marstek ChargeTime till 100%
		if ($hwMarstekSocket > 9 && $marstekBatSoc < 100) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$neededMarstekWh = $marstekCapacityWh - $currentMarstekWh;
			$neededMarstekWhAdjusted = $neededMarstekWh / (1 - $chargerLoss);
			$chargeMarstekTime = $neededMarstekWhAdjusted / $hwMarstekSocket;
			$realMarstekChargeTime = convertTime($chargeMarstekTime);
			
		}
		
// === Marstek DischargeTime till minimum-SOC
		if ($hwMarstekSocket < 0) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$minMarstekWh = (16 / 100) * $marstekCapacityWh;
			$availableMarstekWh = $currentMarstekWh - $minMarstekWh;
			$dischargeMarstekTime = $availableMarstekWh / abs($hwMarstekSocket);
			$realMarstekDischargeTime = convertTime($dischargeMarstekTime);
		}
	}

// = -------------------------------------------------	
// = Determine battery empty time and reset if needed
// = -------------------------------------------------
	/*
	if ($runCharger && !$isManualRun && !empty($vars['battery_empty']) && !empty($vars['battery_empty_time'])) {

		if ($batteryPct >= $chargerPausePct && $batteryPct < 100 && $hoursSinceEmpty >= 1 && $hoursSinceEmpty < 24 && $currentTime >= $sunsetEarly && $currentTime <= "23:59") {
			unset($vars['battery_empty']);
			unset($vars['battery_empty_time']);
			$vars['battery_allowed'] = true;
			$varsChanged = true;
		}
		
		if ($batteryPct >= 50 && $batteryPct < 100 && $hoursSinceEmpty >= 1 && $hoursSinceEmpty < 24 && $currentTime >= "00:00" && $currentTime < $sunrise) {
			unset($vars['battery_empty']);
			unset($vars['battery_empty_time']);
			$vars['battery_allowed'] = true;
			$varsChanged = true;
		}
		
	}
	*/
// = -------------------------------------------------	
// = PushUpdate to Domoticz
// = -------------------------------------------------
	if ($runCharger || $isManualRun){
		
		UpdateDomoticzDevice($batterySOCIDX, ''.$batteryPct.'');
		//sleep(1);
		UpdateDomoticzDevice($marstekSOCIDX, ''.$marstekBatSoc.'');
		//sleep(1);
		UpdateDomoticzDevice($batteryAvailIDX, ''.$batteryAvailable.'');
		//sleep(1);
		UpdateDomoticzDevice($batteryVoltageIDX, ''.$pvAvInputVoltage.'');
		//sleep(1);
		UpdateDomoticzDevice($inputCounterIDX, ''.$hwChargersUsage.'');
		//sleep(1);
		UpdateDomoticzDevice($outputCounterIDX, ''.$hwInvsReturn.'');
		//sleep(1);
		if ($hwChargersUsage > 10 && $batteryPct < 100) {
		UpdateDomoticzDevice($batteryChargeTimeIDX, ''.$realChargeTime.'');
		
		} else {
		UpdateDomoticzDevice($batteryChargeTimeIDX, '00:00');
		}
		//sleep(1);
		if ($hwInvsReturn != 0 && $batteryPct > 0) {
		UpdateDomoticzDevice($batteryDischargeTimeIDX, ''.$realDischargeTime.'');
		} else {
		UpdateDomoticzDevice($batteryDischargeTimeIDX, '00:00');
		}
		
		//sleep(1);
		$chargerLossDomo = ($chargerLoss * 100);
		UpdateDomoticzDevice($batteryRTEIDX, ''.$chargerLossDomo.'');
		
// = Global WriteJson
		//$varsDomoChanged = $varsDomoChanged ?? false;
		//if (!$isManualRun) {
		//	if ($varsDomoChanged) {
		//		writeJsonLocked($varsDomoFile, $varsDomo);
		//	}
		//}
	}

// = -------------------------------------------------	
// = Push data to external Domoticz
// = -------------------------------------------------

	//if ($runCharger && !$isManualRun){
	//	sendBatteryStatusToDomoticz($batteryPct, $mayDischarge);
	//}

?>
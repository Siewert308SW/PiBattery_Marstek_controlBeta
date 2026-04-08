<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Helpers                             //
// **************************************************************//
//

// -------------------------------------------------
// Marstek Set Mode $vars['$battery_allowed'] = true;  && $marstekBatState == 'idle'
// -------------------------------------------------
	if ($runMarstek && !$isManualRun){
		
// === Marstek charged and ready
		if ($marstekBatSoc == 100 && $marstekBatMode == 'Auto' && $marstek_BatModus != 'Charged'){
			$vars['marstek_Modus'] = 'Charged';
			$varsChanged = true;
		}
	
// === Marstek discharged
		if ($marstekBatSoc <= 16 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'Empty'){
			$vars['marstek_Modus'] = 'Empty';
			$varsChanged = true;	
		}

// === piBattery discharged
		if ($marstekBatSoc > 16 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'Empty' && isset($vars['battery_empty'])){
			$vars['marstek_Modus'] = 'Empty';
			$varsChanged = true;	
		}
		
// === Marstek Restart
		if ($marstekBatSoc > 16 && $marstekBatSoc < 90 && $marstekBatMode == 'Passive' && $marstek_BatModus != 'Empty' && $hwChargerUsage == 0 && $hwInvReturn == 0 && $hwSolarReturn <= -500){
			$vars['marstek_Modus'] = 'Empty';
			$varsChanged = true;
		}
		
// === Marstek AUTO mode
		if ($marstekBatMode != 'Auto' && $marstek_BatModus == 'Empty'){
			setMarstekMode('auto');
			$vars['$battery_allowed'] = false;
			$varsChanged = true;
		} 

// === Marstek AUTO mode
		if ($marstekBatMode != 'Passive' && $marstek_BatModus == 'Charged'){
			setMarstekMode('stop');
			$vars['$battery_allowed'] = true;
			$varsChanged = true;
		} 
		
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
			$neededMarstekWhAdjusted = $neededMarstekWh / (1 - 0.15);
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
		UpdateDomoticzDevice($marstekAvailIDX, ''.$marstekAvailable.'');
		//sleep(1);		
		UpdateDomoticzDevice($batteryAvailIDX, ''.$batteryAvailable.'');
		//sleep(1);
		UpdateDomoticzDevice($batteryVoltageIDX, ''.$pvAvInputVoltage.'');
		//sleep(1);
		UpdateDomoticzDevice($inputCounterIDX, ''.$hwChargerUsage.'');
		//sleep(1);
		UpdateDomoticzDevice($outputCounterIDX, ''.$hwInvReturn.'');
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

		if ($hwMarstekSocket > 9 && $marstekBatSoc < 100) {
		UpdateDomoticzDevice($marstekChargeTimeIDX, ''.$realMarstekChargeTime.'');
		
		} else {
		UpdateDomoticzDevice($marstekChargeTimeIDX, '00:00');
		}
		//sleep(1);
		if ($hwMarstekSocket < 0 && $marstekBatSoc > 16) {
		UpdateDomoticzDevice($marstekDischargeTimeIDX, ''.$realMarstekDischargeTime.'');
		} else {
		UpdateDomoticzDevice($marstekDischargeTimeIDX, '00:00');
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
// = Push data to external Domoticz $batteryPct, $mayDischarge
// = -------------------------------------------------

	if ($runCharger && !$isManualRun){
	//if ($isManualRun){	
		sendBatteryStatusToDomoticz();
	}

?>
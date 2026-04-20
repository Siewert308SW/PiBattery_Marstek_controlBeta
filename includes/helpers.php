<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Helpers                             //
// **************************************************************//
//
	
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

	if ($runBaseload && $isManualRun){
// = -------------------------------------------------
// = Fase Protection
// = -------------------------------------------------
	if ($hwP1Fase >= $maxFaseWatts && !$faseProtect && $hwChargerUsage > 0 && !$isManualRun) {
		$vars['faseProtect'] = true;
		$varsChanged = true;

		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On') {
			switchHwSocket('four', 'Off'); sleep(1);
			switchHwSocket('three', 'Off'); sleep(1);
			switchHwSocket('two', 'Off'); sleep(1);
			switchHwSocket('one', 'Off');
		}
		return;

	} elseif ($hwP1Fase < $maxFaseWatts && $faseProtect && $hwChargerUsage == 0 && !$isManualRun) {
		$vars['faseProtect'] = false;
		$varsChanged = true;
	}
	}
	
// = -------------------------------------------------
// = Battery voltage low, keep BMS awake
// = -------------------------------------------------
	if ($runCharger && $pvAvInputVoltage <= $batteryVoltMin && $hwChargersUsage == 0 && $hwInvsReturn == 0 && !$bmsWakeActive && !$isManualRun) {

		if ($hwChargerTwoStatus == 'Off') {
			switchHwSocket('two', 'On');
			sleep(5);
		}

		$vars['bmsWakeActive'] = true;
		$vars['battery_bmsWake_time'] = time();
		$varsChanged = true;
		return;

	//} elseif ($bmsWakeActive && $hoursSince_Wake_time < 0.5 && !$isManualRun && $hwChargerTwoStatus == 'On' && $hwChargersUsage > $chargerWattsIdle) {
	//	return;

	} elseif ($bmsWakeActive && $hoursSince_Wake_time >= 0.5 && !$isManualRun) {

		if ($hwChargerTwoStatus == 'On') {
			switchHwSocket('two', 'Off');
			sleep(5);
		}

		$vars['bmsWakeActive'] = false;
		unset($vars['battery_bmsWake_time']);
		$varsChanged = true;
		return;
	}
//}
	
// = -------------------------------------------------	
// = Estimated charge/discharge time  || $runCharger
// = -------------------------------------------------

	if ($runBaseload || $isManualRun){
// === ChargeTime till 100%
		if ($hwChargersUsage > 10 && $batteryPct < 100) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$neededWh = $batteryCapacityWh - $currentWh;
			$neededWhAdjusted = $neededWh / (1 - $chargerLoss);
			$chargeTime = $neededWhAdjusted / $hwChargersUsage;
			$realChargeTime = convertTime($chargeTime);
			
		}

// === DischargeTime till minimum-SOC
		if ($hwInvsReturn < -10 && $batteryPct > $batteryMinimum) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$minWh = ($batteryMinimum / 100) * $batteryCapacityWh;
			$availableWh = $currentWh - $minWh;
			$dischargeTime = $availableWh / abs($hwInvsReturn);
			$realDischargeTime = convertTime($dischargeTime);
		}

// === Marstek ChargeTime till 100%
		if ($hwMarstekUsage > 10 && $marstekBatSoc < 100) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$neededMarstekWh = $marstekCapacityWh - $currentMarstekWh;
			$neededMarstekWhAdjusted = $neededMarstekWh;
			$chargeMarstekTime = $neededMarstekWhAdjusted / $hwMarstekUsage;
			$realMarstekChargeTime = convertTime($chargeMarstekTime);
			
		}
		
// === Marstek DischargeTime till minimum-SOC
		if ($hwMarstekReturn < -10) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$minMarstekWh = ($marstekMinimum / 100) * $marstekCapacityWh;
			$availableMarstekWh = $currentMarstekWh - $minMarstekWh;
			$dischargeMarstekTime = $availableMarstekWh / abs($hwMarstekReturn);
			$realMarstekDischargeTime = convertTime($dischargeMarstekTime);
		}
	}

// = -------------------------------------------------	
// = PushUpdate to Domoticz
// = -------------------------------------------------
	if ($runBaseload && !$isManualRun){
		
		UpdateDomoticzDevice($batterySOCIDX, ''.$batteryPct.'');
		usleep(100000);
		UpdateDomoticzDevice($marstekSOCIDX, ''.$marstekBatSoc.'');
		usleep(100000);
		UpdateDomoticzDevice($marstekAvailIDX, ''.$marstekAvailable.'');
		usleep(100000);		
		UpdateDomoticzDevice($batteryAvailIDX, ''.$batteryAvailable.'');
		usleep(100000);
		UpdateDomoticzDevice($batteryVoltageIDX, ''.$pvAvInputVoltage.'');
		usleep(100000);
		UpdateDomoticzDevice($inputCounterIDX, ''.$hwChargerUsage.'');
		usleep(100000);
		UpdateDomoticzDevice($outputCounterIDX, ''.$hwInvReturn.'');
		usleep(100000);
		if ($hwChargersUsage > 10 && $batteryPct < 100) {
		UpdateDomoticzDevice($batteryChargeTimeIDX, ''.$realChargeTime.'');
		
		} else {
		UpdateDomoticzDevice($batteryChargeTimeIDX, '00:00');
		}
		usleep(100000);
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
		usleep(100000);
		if ($hwMarstekSocket < 0 && $marstekBatSoc > 16) {
		UpdateDomoticzDevice($marstekDischargeTimeIDX, ''.$realMarstekDischargeTime.'');
		} else {
		UpdateDomoticzDevice($marstekDischargeTimeIDX, '00:00');
		}
		
		usleep(100000);
		$chargerLossDomo = ($chargerLoss * 100);
		UpdateDomoticzDevice($batteryRTEIDX, ''.$chargerLossDomo.'');

	}

// = -------------------------------------------------	
// = Push data to external Domoticz $batteryPct, $mayDischarge
// = -------------------------------------------------

	if ($runBaseload && !$isManualRun){	
		sendBatteryStatusToDomoticz();
	}

?>

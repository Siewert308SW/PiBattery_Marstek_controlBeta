<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Helpers                             //
// **************************************************************//
//

	if ($runCharger && $isManualRun){
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
	
// = -------------------------------------------------	
// = Estimated charge time  || $runCharger
// = -------------------------------------------------

// === piBattery ChargeTime till 100%
		if ($hwChargersUsage > 0 && $batteryPct < 100) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$neededWh = $batteryCapacityWh - $currentWh;
			$neededWhAdjusted = $neededWh / (1 - $chargerLoss);
			$chargeTime = $neededWhAdjusted / $hwChargersUsage;
			$realChargeTime = convertTime($chargeTime);
			
		}
		
// === Marstek ChargeTime till 100%
		if ($hwMarstekUsage > 0 && $marstekBatSoc < 100) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$neededMarstekWh = $marstekCapacityWh - $currentMarstekWh;
			$neededMarstekWhAdjusted = $neededMarstekWh;
			$chargeMarstekTime = $neededMarstekWhAdjusted / $hwMarstekUsage;
			$realMarstekChargeTime = convertTime($chargeMarstekTime);
			
		}

// = -------------------------------------------------	
// = Estimated discharge time  || $runCharger
// = -------------------------------------------------
		
// === piBattery DischargeTime till minimum-SOC
		if ($hwInvsReturn < 0 && $batteryPct > $batteryMinimum) {
			$currentWh = ($batteryPct / 100) * $batteryCapacityWh;
				
			$minWh = ($batteryMinimum / 100) * $batteryCapacityWh;
			$availableWh = $currentWh - $minWh;
			$dischargeTime = $availableWh / abs($hwInvsReturn);
			$realDischargeTime = convertTime($dischargeTime);
		}
		
// === Marstek DischargeTime till minimum-SOC
		if ($hwMarstekReturn < 0) {
			$currentMarstekWh = ($marstekBatSoc / 100) * $marstekCapacityWh;
				
			$minMarstekWh = ($marstekMinimum / 100) * $marstekCapacityWh;
			$availableMarstekWh = $currentMarstekWh - $minMarstekWh;
			$dischargeMarstekTime = $availableMarstekWh / abs($hwMarstekReturn);
			$realMarstekDischargeTime = convertTime($dischargeMarstekTime);
		}

// = -------------------------------------------------	
// = PushUpdate to Domoticz
// = -------------------------------------------------
	if ($runBaseload && !$isManualRun){
		
		if (UpdateDomoticzDeviceIfChanged($batterySOCIDX, ''.$batteryPct.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($marstekSOCIDX, ''.$marstekBatSoc.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($marstekAvailIDX, ''.$marstekAvailable.'') == 'OK') usleep(100000);
		
		if (UpdateDomoticzDeviceIfChanged($batteryAvailIDX, ''.$batteryAvailable.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($batteryVoltageIDX, ''.$pvAvInputVoltage.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($inputCounterIDX, ''.$hwChargersUsage.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($outputCounterIDX, ''.$hwInvsReturn.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($marstekInputCounterIDX, ''.$hwMarstekUsage.'') == 'OK') usleep(100000);

		if (UpdateDomoticzDeviceIfChanged($marstekOutputCounterIDX, ''.$hwMarstekReturn.'') == 'OK') usleep(100000);

// = -------------------------------------------------	

		if ($hwChargersUsage > 10 && $batteryPct < 100) {
			if (UpdateDomoticzDeviceIfChanged($batteryChargeTimeIDX, ''.$realChargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($batteryChargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	

		if ($hwInvsReturn < 0 && $batteryPct > $batteryMinimum) {
			if (UpdateDomoticzDeviceIfChanged($batteryDischargeTimeIDX, ''.$realDischargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($batteryDischargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	

		if ($hwMarstekUsage > 10 && $marstekBatSoc < 100) {
			if (UpdateDomoticzDeviceIfChanged($marstekChargeTimeIDX, ''.$realMarstekChargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($marstekChargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	

		if ($hwMarstekReturn < 0 && $marstekBatSoc > $marstekMinimum) {
			if (UpdateDomoticzDeviceIfChanged($marstekDischargeTimeIDX, ''.$realMarstekDischargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($marstekDischargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	
		
		$chargerLossDomo = ($chargerLoss * 100);
		if (UpdateDomoticzDeviceIfChanged($batteryRTEIDX, ''.$chargerRTE.'') == 'OK') usleep(100000);

// = -------------------------------------------------	
		
		if (UpdateDomoticzDeviceIfChanged($marstekRTEIDX, ''.$marstekRTE.'') == 'OK') usleep(100000);
		
	}

// = -------------------------------------------------	
// = Push data to external Domoticz $batteryPct, $mayDischarge
// = -------------------------------------------------

	if ($runCharger && !$isManualRun){	
		sendBatteryStatusToDomoticz();
	}

?>

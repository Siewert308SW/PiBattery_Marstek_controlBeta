<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Helpers                             //
// **************************************************************//
//															     //


// = -------------------------------------------------	
// = API / Modbus Errors
// = -------------------------------------------------
	if ($runCharger && !$isManualRun){
		
		if ($hwInvOneStatus == 'Off' || $hwInvTwoStatus == 'Off' || $hwMarstekStatus == 'Off'){
			if (!$systemFailure){
			$vars['systemFailure'] = true;
			$vars['systemFailureIssue'] = "Inverter Sockets OFF";
			$varsChanged = true;
			}
		} elseif ($hwInvOneStatus == 'On' && $hwInvTwoStatus == 'On' && $hwMarstekStatus == 'On'){
			if ($systemFailure){
			$vars['systemFailure'] = false;
			$varsChanged = true;
			}
		}			
	}
	
// = -------------------------------------------------	
// = Set piBattery charging pause when battery charged 100%
// = -------------------------------------------------
	if ($runCharger && !$isManualRun){
// === Set Pause
	if (!$isManualRun && (($vars['pauseCharging'] ?? false) !== true) && $hwChargerUsage >= 0 && $hwChargerUsage <= $chargerWattsIdle && $pvAvInputVoltage >= $batteryVoltMax) {
		$pauseCharging = true;
		$battery_awaitingCalibration = true;
		$vars['pauseCharging'] = true;
		$vars['battery_awaitingCalibration'] = true;
		$varsChanged = true;

		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On'){				
			switchHwSocket('four','Off');
			switchHwSocket('three','Off');
			switchHwSocket('two','Off');
			switchHwSocket('one','Off');
		}

		if ((($vars['pauseMarstekCharging'] ?? true) !== false) && $marstekSoc <= 95) {
			$vars['pauseMarstekCharging'] = false;
			$vars['marstek_force_mode'] = '';
		}
	
		return;	
	}

// === End pause
	if (!$isManualRun && (($vars['pauseCharging'] ?? true) !== false) && $batteryPct <= $chargerPausePct) { // && $hwInvReturn == 0
		$vars['pauseCharging'] = false;
		$varsChanged = true;
	}

// = -------------------------------------------------	
// = Set Marstek charging pause when battery charged 100%
// = -------------------------------------------------	
// === Set Pause
	if (!$isManualRun && (($vars['pauseMarstekCharging'] ?? false) !== true) && $marstekSoc == 100 && $hwMarstekSocket >= 0 && $hwMarstekSocket < 15) {
		$pauseMarstekCharging = true;
		$vars['pauseMarstekCharging'] = true;
		$vars['marstek_force_mode'] = '';
		$varsChanged = true;
		//setMarstekUsage(0);
		
		if ((($vars['pauseCharging'] ?? true) !== false) && $batteryPct <= 95) {
			$vars['pauseCharging'] = false;
		}
		return;	
	}
	
// === End pause
	if (!$isManualRun && (($vars['pauseMarstekCharging'] ?? true) !== false) && $marstekSoc <= $chargerPausePct) {
		$vars['pauseMarstekCharging'] = false;
		$vars['marstek_force_mode'] = '';
		$varsChanged = true;
	}

	}
	
// = -------------------------------------------------	
// = Batt% variable calibration
// = -------------------------------------------------
	if ($runCharger && !$isManualRun){
		if ($pvAvInputVoltage >= $batteryVoltMax && $pauseCharging && !isset($vars['battery_awaitingCalibration']) && !$isManualRun && !isset($vars['battery_calibrated'])) {
				$varsChanged = true;
				$vars['battery_awaitingCalibration'] = true;
		}
			
		if ($pvAvInputVoltage >= $batteryVoltMax && $pauseCharging && !$isManualRun
			&& $hwChargerOneStatus == 'Off' && $hwChargerTwoStatus == 'Off' && $hwChargerThreeStatus == 'Off' && $hwChargerFourStatus == 'Off'
			&& (!isset($vars['battery_calibrated']) || $vars['battery_calibrated'] !== true)
			) {

			$chargeStart  		= round($hwChargersTotalInput, 7);
			$chargeCalibrated	= round($hwChargersTotalInput - $batteryCapacitykWh, 7);
			$dischargeStart 	= round($hwInvTotal, 7);

	// = Start Charge Loss Calculation
		if (!isset($vars['charge_loss_calculation']) || $vars['charge_loss_calculation'] !== true){
			$vars['charging_loss'] = [
				'chargeStart' => $chargeStart,
				'dischargeStart' => $dischargeStart
			];
				
			$vars['charge_loss_calculation'] = true;
			unset($vars['battery_awaitingCalibration']);
			$varsChanged = true;			
			return;
				
		} elseif ($vars['charge_loss_calculation'] === true) {

			$chargedkWh    = $brutoCharged;
			$dischargedkWh = $brutoDischarged;
						
			if ($chargedkWh > 0 && $dischargedkWh > 0 && $dischargedkWh <= $chargedkWh) {
				$sessionLoss = 1 - ($dischargedkWh / $chargedkWh);

	// === Log session only if new
			$sessionFile = $piBatteryPath . 'data/charge_sessions.json';
			$newSession = [
			'charged'     		 => round($chargedkWh, 7),
			'discharged'     	 => round($dischargedkWh, 7),
			'loss'        		 => round($sessionLoss, 7)
			];

			$sessions = [];
			$skipSession = false;

			if (file_exists($sessionFile)) {
				$sessions = json_decode(file_get_contents($sessionFile), true);
				if (!is_array($sessions)) $sessions = [];

	// === Check id session is identical to the latest session
				$lastSession = end($sessions);
				if (
					isset($lastSession['charged'], $lastSession['discharged']) &&
					$newSession['charged'] === $lastSession['charged'] &&
					$newSession['discharged'] === $lastSession['discharged']
					) {
					$skipSession = true;
					}
				}

				if (!$skipSession) {
					$sessions[] = $newSession;

	// === Remove oldest session				
			if (count($sessions) > $chargeSessions) {
			array_shift($sessions);
			}	
			writeJsonLocked($sessionFile, $sessions);
			}

	// === Calculate session average
			$losses = [];
			foreach ($sessions as $s) {
				if (isset($s['charged'], $s['discharged']) &&
					$s['charged'] > 0 &&
					$s['discharged'] > 0 &&
					$s['charged'] >= $s['discharged']
					) {
					$loss = 1 - ($s['discharged'] / $s['charged']);
					$losses[] = $loss;
				}
			}

			if (count($losses) >= $chargeSessions) {
				$chargerLoss = array_sum($losses) / count($losses);
				if ($chargerLoss != $vars['charger_loss_dynamic']) {
				$varsChanged = true;
				$vars['charger_loss_dynamic'] = $chargerLoss;
				}
			}
				
				if (isset($vars['charge_loss_calculation'])) {
					$varsChanged = true;
					unset($vars['charge_loss_calculation']);
						
					if (isset($vars['battery_empty'])) {
						unset($vars['battery_empty']);
						$varsChanged = true;
					}
				}
			}
		}

	// = End Charge Loss calculation
			$varsChanged = true;
			$vars['charge_session'] = [
				'chargeStart'     => $chargeStart,
				'chargeCalibrated'=> $chargeCalibrated,
				'dischargeStart'  => $dischargeStart
			];
					
			$vars['battery_calibrated'] = true;
			unset($vars['battery_awaitingCalibration']);
			$varsChanged = true;
		}

		if (isset($vars['battery_calibrated']) && $pvAvInputVoltage < $batteryVoltMax) {
			unset($vars['battery_calibrated']);
			$varsChanged = true;
		}
	}
// = -------------------------------------------------
// = Fase Protection
// = -------------------------------------------------
	if ($runCharger && !$isManualRun){
		if ($hwP1Fase >= $maxFaseWatts && !$faseProtect && $hwChargerUsage > 0 && !$isManualRun) {
			$vars['faseProtect'] = true;
			$varsChanged = true;

			if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On') {
				switchHwSocket('four', 'Off');
				switchHwSocket('three', 'Off');
				switchHwSocket('two', 'Off');
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
	if ($runCharger && !$isManualRun && $pvAvInputVoltage <= $batteryVoltMin && $hwChargersUsage == 0 && $hwInvsReturn == 0 && !$bmsWakeActive && !$isManualRun) {

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
// = Estimated charge time
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
		if ($hwMarstekUsage > 0 && $marstekSoc < 100) {
			$currentMarstekWh = ($marstekSoc / 100) * $marstekCapacityWh;
				
			$neededMarstekWh = $marstekCapacityWh - $currentMarstekWh;
			$neededMarstekWhAdjusted = $neededMarstekWh;
			$chargeMarstekTime = $neededMarstekWhAdjusted / $hwMarstekUsage;
			$realMarstekChargeTime = convertTime($chargeMarstekTime);
			
		}
	
// = -------------------------------------------------	
// = Estimated discharge time
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
			$currentMarstekWh = ($marstekSoc / 100) * $marstekCapacityWh;
				
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

		if (UpdateDomoticzDeviceIfChanged($marstekSOCIDX, ''.$marstekSoc.'') == 'OK') usleep(100000);

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

		if ($hwMarstekUsage > 10 && $marstekSoc < 100) {
			if (UpdateDomoticzDeviceIfChanged($marstekChargeTimeIDX, ''.$realMarstekChargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($marstekChargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	

		if ($hwMarstekReturn < 0 && $marstekSoc > $marstekMinimum) {
			if (UpdateDomoticzDeviceIfChanged($marstekDischargeTimeIDX, ''.$realMarstekDischargeTime.'') == 'OK') usleep(100000);
		} else {
			if (UpdateDomoticzDeviceIfChanged($marstekDischargeTimeIDX, '00:00') == 'OK') usleep(100000);
		}

// = -------------------------------------------------	
		
		if (UpdateDomoticzDeviceIfChanged($batteryRTEIDX, ''.$chargerRTE.'') == 'OK') usleep(100000);

// = -------------------------------------------------	

		if (UpdateDomoticzDeviceIfChanged($marstekRTEIDX, ''.$marstekRTE.'') == 'OK') usleep(100000);
		
// = -------------------------------------------------	
		
		if (UpdateDomoticzDeviceIfChanged($ecoFlowTempIDX, ''.$invTemp.'') == 'OK') usleep(100000);

// = -------------------------------------------------	
		
		if (UpdateDomoticzDeviceIfChanged($marstekTempIDX, ''.$marstekTemp.'') == 'OK') usleep(100000);
		
	}

// = -------------------------------------------------	
// = Push data to Domoticz
// = -------------------------------------------------

	if ($runCharger && !$isManualRun){	
		sendBatteryStatusToDomoticz();
	}

?>

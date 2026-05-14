<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Charging                            //
// **************************************************************//
//																 //

// = -------------------------------------------------	
// = Set piBattery charging pause when battery charged 100%
// = -------------------------------------------------	
// === Set Pause
	if (!$isManualRun && (($vars['pauseCharging'] ?? false) !== true) && $hwChargerUsage >= 0 && $hwChargerUsage <= $chargerWattsIdle && $pvAvInputVoltage >= $batteryVoltMax) {
		$vars['pauseCharging'] = true;
		$vars['battery_awaitingCalibration'] = true;
		$varsChanged = true;

		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On'){				
			switchHwSocket('four','Off'); sleep(1);
			switchHwSocket('three','Off'); sleep(1);
			switchHwSocket('two','Off'); sleep(1);
			switchHwSocket('one','Off');
		}
		
		return;	
	}

// === End pause
	if (!$isManualRun && (($vars['pauseCharging'] ?? true) !== false) && $batteryPct <= $chargerPausePct) {
		$vars['pauseCharging'] = false;
		$varsChanged = true;
	}

// = -------------------------------------------------	
// = Set Marstek charging pause when battery charged 100%
// = -------------------------------------------------	
// === Set Pause including failsave due to buggy Marstek API
	if (!$isManualRun && (($vars['pauseMarstekCharging'] ?? false) !== true) && $marstekBatSoc >= 99 && $hwMarstekSocket >= 0 && $hwMarstekSocket < 15) {
		$vars['pauseMarstekCharging'] = true;
		$varsChanged = true;
		setMarstekUsage(0);
		return;	
	}
	
// === End pause
	if (!$isManualRun && (($vars['pauseMarstekCharging'] ?? true) !== false) && $marstekBatSoc <= $chargerPausePct) { 
		$vars['pauseMarstekCharging'] = false;
		$varsChanged = true;
	}
	
// = -------------------------------------------------	
// = Batt% variable calibration
// = -------------------------------------------------

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
		
// = -------------------------------------------------
// = Keep chargers OFF #failsaves
// = -------------------------------------------------
	//$highConsumption = ($realUsage > 2000 && $hwChargersUsage == 0 && $pauseMarstekCharging);
	$charged		 = ($pauseCharging && $pauseMarstekCharging);
	
	if (
		!$keepChargersOff &&
		(
			$faseProtect ||
			$hwInvReturn < 0 ||
			isset($vars['battery_awaitingCalibration']) ||
			$battery_calibrated || 
			$chargeLossCalculation ||
			//$highConsumption ||
			$charged
		)
	) {
		$vars['keepChargersOff'] = true;
		$varsChanged = true;

	} elseif (
		$keepChargersOff &&
		!$faseProtect &&
		$hwInvReturn == 0 &&
		!isset($vars['battery_awaitingCalibration']) &&
		!$battery_calibrated && 
		!$chargeLossCalculation &&
		//!$highConsumption &&
		!$charged
	) {
		$vars['keepChargersOff'] = false;
		$varsChanged = true;
	}

// = -------------------------------------------------
// = Get chargers total usage
// = -------------------------------------------------
	$currentTotal = 0;

// = Get status for all chargers
	foreach ($chargers as $name => &$data) {
		$data['status'] = getHwStatus($data['ip']);
		if ($data['status'] === 'On') {
			$currentTotal += $data['power'];
		}
	}
	unset($data);

// = -------------------------------------------------
// = Determine available PV surplus  - $chargerhyst
// = -------------------------------------------------
	$grossAvailableSolarPower = 0;
	
	if ($hwChargerUsage == 0) {
	$grossAvailableSolarPower = max(0, -$P1ChargerUsage - $chargerhyst);		
	} elseif ($hwChargerUsage > 0) {
	$grossAvailableSolarPower = max(0, -$P1ChargerUsage - 50);		
	}

// = -------------------------------------------------
// = Marstek virtual charger (dynamic priority charger)
// = -------------------------------------------------
	$marstekVirtualChargerTarget   = 0;
	$marstekVirtualChargerMin      = 100;
	$marstekVirtualChargerMax      = 2000;
	$marstekVirtualChargerDelta    = 100;
	$marstekVirtualChargerPower    = (int)($vars['marstek_virtual_charger_power'] ?? 0);
	$marstekAvailableForChargers   = $grossAvailableSolarPower;

	if (!$pauseMarstekCharging && !$bmsWakeActive) {
		if ($grossAvailableSolarPower >= $marstekVirtualChargerMin) {
			$marstekVirtualChargerTarget = min($marstekVirtualChargerMax, $grossAvailableSolarPower);
		}
	}

	$marstekDelta = abs($marstekVirtualChargerTarget - $hwMarstekUsage);

// === Update Marstek
	if ($marstekDelta >= $marstekVirtualChargerDelta) {

		$marstekVirtualChargerPower = $marstekVirtualChargerTarget;
			
		if (!$isManualRun && !$keepChargersOff && !$pauseMarstekCharging && !$bmsWakeActive) {
			setMarstekUsage($marstekVirtualChargerPower);
		}
	}

// === Use actual/applied Marstek power for remaining solar
	$marstekAvailableForChargers = max(0, $grossAvailableSolarPower - $hwMarstekUsage);
	if ($pauseMarstekCharging) {		
	$availableSolarPower = $marstekAvailableForChargers;
	} else {		
	$availableSolarPower = 0;
	}
	
	if ($debug == 'yes' && $isManualRun && !$bmsWakeActive) {
		debugMsg("Beschikbaar overschot totaal: {$grossAvailableSolarPower}W");
		debugMsg("Marstek target: {$marstekVirtualChargerTarget}W");
		debugMsg("Marstek werkelijk: {$hwMarstekUsage}W");
		debugMsg("Marstek delta: {$marstekDelta}W");
		debugMsg("Resterend overschot voor fysieke laders: {$availableSolarPower}W");
	}
	
// = -------------------------------------------------
// = Find master charger
// = -------------------------------------------------
	$masterName = null;

	foreach ($chargers as $name => $data) {
		if (!empty($data['master'])) {
			$masterName = $name;
			break;
		}
	}

	if ($masterName === null) {
		if ($debug == 'yes' && $isManualRun) {
			debugMsg("Geen master lader gedefinieerd!");
		}
		return;
	}

// = -------------------------------------------------
// = Build valid charger combinations
// = -------------------------------------------------
	$names = array_keys($chargers);
	$n = count($names);
	$validCombinations = [];

	for ($i = 1; $i < (1 << $n); $i++) {
		$combi = [];
		$totalChargerUsage = 0;
		$masterInCombination = false;
		$containsRestricted = false;
		$restrictedName = null;

		for ($j = 0; $j < $n; $j++) {
			if ($i & (1 << $j)) {
				$name = $names[$j];
				$combi[] = $name;
				$totalChargerUsage += $chargers[$name]['power'];

				if ($name === $masterName) {
					$masterInCombination = true;
				}

				if (!empty($chargers[$name]['spare_charger'])) {
					$containsRestricted = true;
					$restrictedName = $name;
				}
			}
		}

		// Master charger has to be set and always ON before building BestCombi
		if (!$masterInCombination) {
			continue;
		}

		// Spare charger 
		if ($containsRestricted) {
			$otherChargers = array_diff(array_keys($chargers), [$restrictedName]);
			if (count(array_intersect($combi, $otherChargers)) !== count($otherChargers)) {
				continue;
			}
		}

		// All chargers which fits within Solar Surplus
		if ($totalChargerUsage <= $availableSolarPower) {
			$validCombinations[] = [
				'names' => $combi,
				'total' => $totalChargerUsage
			];
		}
	}

// = -------------------------------------------------
// = Choose best charger combination
// = -------------------------------------------------
	usort($validCombinations, function ($a, $b) {
		return $b['total'] <=> $a['total'];
	});

	$bestCombi = $validCombinations[0]['names'] ?? [];
	$bestTotal = $validCombinations[0]['total'] ?? 0;

	if ($debug == 'yes' && $isManualRun && !$bmsWakeActive) {
		if (!empty($bestCombi)) {
			debugMsg("Beste lader combinatie - " . implode(', ', $bestCombi) . " ({$bestTotal}W)");
		} else {
			debugMsg("Geen geldige lader combinatie gevonden - alles uit");
		}
	}

// = -------------------------------------------------	
// = Check if toggling chargers is upscale or downscale  && $currentTotal > 0  && $currentTotal > 0
// = -------------------------------------------------
	$isUpscaling 		= ($bestTotal > $currentTotal);
	$isDownscaling 		= ($bestTotal < $currentTotal);
	$isMinimumCombi 	= empty($bestCombi) || $bestCombi === [$masterName];
	$currentPendingType = null;

	if ($isUpscaling) {
		$currentPendingType = 'upscale';
		
	} elseif ($isDownscaling) {
		$currentPendingType = 'downscale';
	}
		
	$chargerPauseNeeded = ($currentPendingType !== null);
	$chargerToggleNeeded = false;

// = -------------------------------------------------
// = Check if toggling is needed
// = -------------------------------------------------
	$chargerToggleNeeded = false;

	foreach ($chargers as $name => $data) {
		$shouldBeOn = in_array($name, $bestCombi, true);
		$isOn = ($data['status'] === 'On');

		if ($shouldBeOn !== $isOn && !$pauseCharging) {
			$chargerToggleNeeded = true;
			break;
		}
	}

// = -------------------------------------------------	
// = Reset pending switch if situation changed
// = -------------------------------------------------
	if ($pendingSwitch && ($vars['charger_pending_type'] ?? null) !== $currentPendingType && !$pauseCharging) {
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Pending switch reset: situatie gewijzigd van ".($vars['charger_pending_type'] ?? 'unknown')." naar ".($currentPendingType ?? 'none'));
		}

		if (!$isManualRun) {
			$varsChanged = true;
			$vars['charger_pending_switch'] = false;
			unset($vars['charger_pause_until']);
			unset($vars['charger_pending_type']);
		}

		$pendingSwitch = false;
		$pauseUntil = 0;
	}

// = -------------------------------------------------	
// = Pauze toggling chargers ON/OFF
// = -------------------------------------------------
	$chargerToggleAllowed = false;

// === Pause toggling chargers	
	if ($pauseUntil >= $currentTimestamp && $chargerToggleNeeded && $bmsWakeActive == false && !$pauseCharging) {
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Pauze actief tot " . date("H:i:s", $pauseUntil) . ", geen actie");
		}
		return;
	}

	if ($pendingSwitch && $chargerToggleNeeded && $bmsWakeActive == false && !$pauseCharging) {
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Pauze verlopen, schakeling uitvoeren");
		}
			
		$chargerToggleAllowed = true;			

		if ($vars['charger_pending_switch'] == true && !$isManualRun) {
		$varsChanged = true;		
		$vars['charger_pending_switch'] = false;
		unset($vars['charger_pause_until']);
		unset($vars['charger_pending_type']);
		}
			
	} elseif (!$pendingSwitch && $chargerToggleNeeded && $chargerPause > 0 && $chargerPauseNeeded == true && $bmsWakeActive == false && !$pauseCharging) {
		$newPauseUntil = time() + $chargerPause;
		if (
			($vars['charger_pause_until'] ?? 0) !== $newPauseUntil ||
			($vars['charger_pending_switch'] ?? false) !== true && !$isManualRun
		) {
			if ($vars['charger_pending_switch'] == false) {
			$varsChanged = true;	
			$vars['charger_pause_until'] = $newPauseUntil;
			$vars['charger_pending_switch'] = true;
			$vars['charger_pending_type'] = $currentPendingType;
			}
		}
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Schakeling vereist, pauze gestart tot " . date("H:i:s", $vars['charger_pause_until']));
		}
		return;

	} elseif (!$pendingSwitch && $chargerToggleNeeded && $chargerPauseNeeded == false && $bmsWakeActive == false && !$pauseCharging) {
		$chargerToggleAllowed = true;
	}
	
// = -------------------------------------------------
// = Toggle chargers ON/OFF
// = -------------------------------------------------
	if ($chargerToggleNeeded == true && $chargerToggleAllowed == true && !$bmsWakeActive) {
		
		foreach ($chargers as $name => $data) {
			$shouldBeOn = in_array($name, $bestCombi, true);
			$isOn = ($data['status'] === 'On');

			if ($shouldBeOn && !$isOn) {
				if (!$isManualRun && !$keepChargersOff) {
					switchHwSocket($data['label'], 'On');
				}

				if ($debug == 'yes' && $isManualRun) {
					debugMsg("Inschakelen: $name");
				}

			} elseif (!$shouldBeOn && $isOn) {
				if (!$isManualRun) {
					switchHwSocket($data['label'], 'Off');
				}

				if ($debug == 'yes' && $isManualRun) {
					debugMsg("Uitschakelen: $name");
				}
			}
		}
	}

// === Reset Pause
	if ($chargerToggleNeeded == false && $vars['charger_pending_switch'] == true && !$isManualRun) {
		$varsChanged = true;		
		$vars['charger_pending_switch'] = false;
		unset($vars['charger_pause_until']);
		unset($vars['charger_pending_type']);
	}
?>
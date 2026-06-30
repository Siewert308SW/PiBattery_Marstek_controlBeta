<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Charging                            //
// **************************************************************//
//																 //

// = -------------------------------------------------
// = Keep chargers OFF #failsaves
// = -------------------------------------------------
	$charged = ($pauseCharging && $pauseMarstekCharging);
	
	if (
		!$keepChargersOff &&
		(
			$faseProtect ||
			$hwInvReturn < 0 ||
			isset($vars['battery_awaitingCalibration']) ||
			$battery_calibrated || 
			$chargeLossCalculation ||
			$charged
		)
	) {
		$keepChargersOff = true;
		$vars['keepChargersOff'] = true;
		$varsChanged = true;

	} elseif (
		$keepChargersOff &&
		!$faseProtect &&
		$hwInvReturn == 0 &&
		!isset($vars['battery_awaitingCalibration']) &&
		!$battery_calibrated && 
		!$chargeLossCalculation &&
		!$charged
	) {
		$keepChargersOff = false;
		$vars['keepChargersOff'] = false;
		$varsChanged = true;
	}

// = -------------------------------------------------
// = Determine available PV surplus
// = -------------------------------------------------
	$grossAvailableSolarPower = 0;
	$grossAvailableSolarPower = max(0, -$P1ChargerUsage - $chargerhyst);
	
// = -------------------------------------------------
// = Marstek dynamic charger
// = -------------------------------------------------
	$marstekChargerTarget = 0;
	$keepMarstekOff = ($faseProtect || $hwInvReturn < 0 || $bmsWakeActive || $pauseMarstekCharging || $hwMarstekStatus == 'Off');
	
	if (!$keepMarstekOff) {
		if ($grossAvailableSolarPower >= ($marstekChargerMin)) {
			$rounded = floor($grossAvailableSolarPower / $marstekChargerStep) * $marstekChargerStep;
			$marstekChargerTarget = min($marstekChargerMax, max($marstekChargerMin, $rounded));
		}
	}

// === Update Marstek
	$marstekDelta = abs($marstekChargerTarget - $hwMarstekUsage);
	if ($marstekDelta >= $marstekChargerStep) {
		if (!$isManualRun) {
			setMarstekUsage($marstekChargerTarget);
		}
	}

// === Marstek fully charged? then give remaining surplus to piBattery
	if ($pauseMarstekCharging) {		
	$availableSolarPower = floor($grossAvailableSolarPower);
	} else {		
	$availableSolarPower = 0;
	}

// = -------------------------------------------------
// = Get chargers total usage
// = -------------------------------------------------
	$currentTotal = 0;
	foreach ($chargers as $name => &$data) {
		$data['status'] = getHwStatus($data['ip']);
		if ($data['status'] === 'On') {
			$currentTotal += $data['power'];
		}
	}
	unset($data);
	
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

// = -------------------------------------------------
// = Determin is current bestCombi is still within surplus
// = -------------------------------------------------
	$currentCombi = [];
	foreach ($chargers as $name => $data) {
		if ($data['status'] === 'On') {
			$currentCombi[] = $name;
		}
	}
	$currentCombiTotal = array_sum(array_map(fn($n) => $chargers[$n]['power'], $currentCombi));
	$grossAvailableWithoutHyst = max(0, -$P1ChargerUsage);

	if (!empty($currentCombi) && in_array($masterName, $currentCombi) && $currentCombiTotal <= $grossAvailableWithoutHyst && $bestTotal < $currentCombiTotal) {
		$bestCombi = $currentCombi;
		$bestTotal = $currentCombiTotal;
	}
	
	if ($debug == 'yes' && $isManualRun && !$bmsWakeActive) {
		if (!empty($bestCombi) && !$keepChargersOff) {
			debugMsg("Beste lader combinatie - " . implode(', ', $bestCombi) . "");
		}
	}

// = -------------------------------------------------	
// = Check if toggling chargers is upscale or downscale
// = -------------------------------------------------
	$isUpscaling 		= ($bestTotal > $currentTotal);
	$isDownscaling 		= ($bestTotal < $currentTotal);
	$currentPendingType = null;

	if ($isUpscaling && $hwChargerUsage == 0) {
		$currentPendingType = 'upscale';
		
	} elseif ($isDownscaling && $hwP1Usage < $currentTotal) {
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
	if ($pendingSwitch && ($vars['charger_pending_type'] ?? null) !== $currentPendingType && !$pauseCharging && !$keepChargersOff) {
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
	if ($pauseUntil >= $currentTimestamp && $chargerToggleNeeded && $bmsWakeActive == false && !$pauseCharging && !$keepChargersOff) {
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Pauze actief tot " . date("H:i:s", $pauseUntil) . ", geen actie");
		}
		return;
	}

	if ($pendingSwitch && $chargerToggleNeeded && $bmsWakeActive == false && !$pauseCharging && !$keepChargersOff) {
		if ($debug == 'yes' && $isManualRun){
		debugMsg("Pauze verlopen, schakeling uitvoeren");
		}
			
		$chargerToggleAllowed = true;			

		if ($pendingSwitch && !$isManualRun) {
		$varsChanged = true;		
		$vars['charger_pending_switch'] = false;
		unset($vars['charger_pause_until']);
		unset($vars['charger_pending_type']);
		}
			
	} elseif (!$pendingSwitch && $chargerToggleNeeded && $chargerPause > 0 && $chargerPauseNeeded == true && $bmsWakeActive == false && !$pauseCharging && !$keepChargersOff) {
		$newPauseUntil = time() + $chargerPause;
		if (
			($vars['charger_pause_until'] ?? 0) !== $newPauseUntil ||
			(!$pendingSwitch && !$isManualRun)
		) {
			if (!$pendingSwitch) {
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

	} elseif (!$pendingSwitch && $chargerToggleNeeded && $chargerPauseNeeded == false && $bmsWakeActive == false && !$pauseCharging && !$keepChargersOff) {
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
	if ($chargerToggleNeeded == false && $pendingSwitch && !$isManualRun) {
		$varsChanged = true;		
		$vars['charger_pending_switch'] = false;
		unset($vars['charger_pause_until']);
		unset($vars['charger_pending_type']);
	}
?>

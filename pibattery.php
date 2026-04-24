<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                                                               //
// **************************************************************//
//																 //

// = -------------------------------------------------
// = Function writePiJson
// = -------------------------------------------------
	function writePiJson(string $filename, array $data): void {
		$fp = @fopen($filename, 'c+');
		if (!$fp) return;

		if (flock($fp, LOCK_EX)) {
			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
			fflush($fp);
			flock($fp, LOCK_UN);
		}
		fclose($fp);
	}

// = -------------------------------------------------
// = Variables
// = -------------------------------------------------
	
// = Time & File Variables
	$timeStamp 				= time();
	$currentTime 			= date('H:i');
	$dateNow 				= date('Y-m-d H:i:s');
	$piBatteryPath 			= __DIR__ . '/';
	$varsTimerFile 			= $piBatteryPath . 'data/timeStamp.json';
	$bootStrapFile			= $piBatteryPath . 'bootstrap/bootstrap.php';
	
	$scriptTimer 			= [];
	$scriptTimer 			= file_exists($varsTimerFile) ? json_decode(file_get_contents($varsTimerFile), true) : [];

	$runCharger 			= false;
	$runBaseload 			= false;
	$runMarstek 			= false;
	$varsPiChanged 			= false;
	$varsChanged			= false;
	$lockChanged 			= false;
	
// = Determine is script called by terminal
	$isCliInteractive 		= function_exists('posix_isatty') && posix_isatty(STDOUT);
	$isManualRun 			= php_sapi_name() === 'cli' && $isCliInteractive;
	$isCronRun 				= php_sapi_name() === 'cli' && !$isCliInteractive;

// = -------------------------------------------------
// = Determine if script may be executed
// = -------------------------------------------------
	
// = Determine if Charger script may execute
	if(!$isManualRun) {
		if (!isset($scriptTimer['lastChargerRun']) || ($timeStamp - $scriptTimer['lastChargerRun']) >= 60) {
			$runCharger = true;
		}

	// = Determine if Baseload script may be executed
		if (!isset($scriptTimer['lastBaseloadRun']) || ($timeStamp - $scriptTimer['lastBaseloadRun']) >= 20) {
			$runBaseload = true;
		}
		
	// = Determine if Marstek API script may be executed
		if (!isset($scriptTimer['lastMarstekRun']) || ($timeStamp - $scriptTimer['lastMarstekRun']) >= 60) {
			$runMarstek = true;
		}
	}
	
// = -------------------------------------------------
// = Script may be executed
// = -------------------------------------------------
	if ($runCharger == true || $runBaseload == true || $runMarstek == true || $isManualRun) {
		require_once $bootStrapFile;
	}

// = Baseload script may be executed	
	if ($runBaseload == true || $isManualRun) {
		$scriptTimer['lastBaseloadRun'] = $timeStamp;
		writePiJson($varsTimerFile, $scriptTimer);
		require_once $piBatteryPath . 'scripts/baseload.php';
	}

// = Charger script may execute
	if ($runCharger == true || $isManualRun) {
		$scriptTimer['lastChargerRun'] = $timeStamp;
		writePiJson($varsTimerFile, $scriptTimer);
		usleep(500000);
		require_once $piBatteryPath . 'scripts/charge.php';
	}
	
// = Marstek API script may be executed	
	if ($runMarstek == true && !$isManualRun) {
		$scriptTimer['lastMarstekRun'] = $timeStamp;
		writePiJson($varsTimerFile, $scriptTimer);
		usleep(10000000);
		require_once $piBatteryPath . 'scripts/marstek.php';
	}

// = -------------------------------------------------
// = Debug Output
// = -------------------------------------------------
	if ($debug == 'yes' && $isManualRun){
		echo ' '.PHP_EOL;
		echo '  ---------------------------------------------------'.PHP_EOL;
	    echo '  --                   PiBattery                   --'.PHP_EOL;
		echo '  --            '.$totalCapacitykWh.' kWh Solar Storage             --'.PHP_EOL;
		echo '  ---------------------------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;

// === Print Charger Status	
		echo ' -/- Laders                          -\-'.PHP_EOL;
		printRow("Lader 1 @ {$hwChargerOneUsage}W", $hwChargerOneStatus);
		printRow("Lader 2 @ {$hwChargerTwoUsage}W", $hwChargerTwoStatus);
		printRow("Lader 3 @ {$hwChargerThreeUsage}W", $hwChargerThreeStatus);
		printRow("Lader 4 @ {$hwChargerFourUsage}W", $hwChargerFourStatus);
		printRow("Marstek @ {$hwMarstekUsage}W", $hwMarstekStatus);
		printRow('Laders verbruik', $hwChargerUsage, 'Watt');
		echo ' '.PHP_EOL;
	
// === Print Batterij Status		
		echo " -/- PiBatterij @ {$batteryCapacitykWh} kWh           -\-".PHP_EOL;
		printRow('Batterij SOC', $batteryPct, '%');
		printRow('Batterij voltage', $pvAvInputVoltage, 'Volt');
		printRow('Batterij beschikbaar', round($batteryAvailable, 2), 'kWh');
		printRow('Laad verlies (gemiddeld)',  round($chargerLoss * 100, 3), '%');
		if ($hwChargersUsage > 10 && $batteryPct < 100) {
			printRow('Geschatte oplaadtijd '.round($batteryPct,0).'% > 100%', $realChargeTime, 'u/m');
		}
		if ($hwInvsReturn < 0 && $batteryPct > $batteryMinimum) {
			printRow('Geschatte ontlaadtijd '.$batteryMinimum.'% < '.round($batteryPct,0).'%', $realDischargeTime, 'u/m');
		}
		printRow("Batterij actief", ($usePiBattery ? 'Ja' : 'Nee'), '');
		echo ' '.PHP_EOL;

// === Print Marstek Status 
		echo " -/- Marstek @ {$marstekCapacitykWh} kWh              -\-".PHP_EOL;
		printRow('Marstek SOC', $marstekBatSoc, '%');
		printRow('Marstek beschikbaar', round($marstekAvailable, 2), 'kWh');

		if ($hwMarstekSocket > 9 && $marstekBatSoc < 100) {
			printRow('Geschatte oplaadtijd '.round($marstekBatSoc,0).'% > 100%', $realMarstekChargeTime, 'u/m');
		}
		
		if ($hwMarstekSocket < 0 && $marstekBatSoc > $marstekMinimum) {
			printRow('Geschatte ontlaadtijd '.$marstekMinimum.'% < '.round($marstekBatSoc,0).'%', $realMarstekDischargeTime, 'u/m');
		}
		
		printRow("Marstek actief", ($useMarstek ? 'Ja' : 'Nee'), '');
		echo ' '.PHP_EOL;
		
// === Print Inverter Status 
		echo ' -/- Omvormers                       -\-'.PHP_EOL;
		printRow('EcoFlow #1 Output', $hwInvOneReturn, 'Watt');
		printRow('Ecoflow #2 Output', $hwInvTwoReturn, 'Watt');
		printRow('Marstek #1 Output', $hwMarstekReturn, 'Watt');
		printRow('Omvormers Temperatuur', $invTemp, '°C');
		echo ' '.PHP_EOL;
		
// === Print Energie Status		
		echo ' -/- Energie                         -\-'.PHP_EOL;
		printRow('Echte verbruik', $realUsage, 'Watt');
		printRow('P1-Meter', $hwP1Usage, 'Watt');
		printRow('Zonnepanelen opwek', $hwSolarReturn, 'Watt');
		printRow('Batterij opwek', $hwInvReturn, 'Watt');
		printRow('Overschot voor de laders', $P1ChargerAvailable, 'Watt');
		echo ' '.PHP_EOL;

// === Print Baseload
		echo ' -/- Baseload                        -\-'.PHP_EOL;
		printRow('Ingestelde baseload', $currentBaseload, 'Watt');
		printRow('Gemeten output', abs($hwInvReturn), 'Watt');
		printRow('Nieuwe baseload', ($newBaseload / 10), 'Watt');
		printRow('Delta target/gemeten', ($delta / 10), 'Watt');
		printRow('Baseload update nodig', ($updateNeeded ? 'true' : 'false'));
		echo ' '.PHP_EOL;
	
// === Print Various
		echo ' -/- Various                         -\-'.PHP_EOL;
		printRow('Batterij laad pauze '.$chargerPausePct.'% <-> 100%', ($pauseCharging ? 'Actief' : 'Niet actief'));
		printRow('Marstek laad pauze '.$chargerPausePct.'% <-> 100%', ($pauseMarstekCharging ? 'Actief' : 'Niet actief'));
		printRow("BMS bescherming", ($bmsWakeActive ? "Actief" : "Niet actief"));
		printRow("Fase {$fase} bescherming", ($faseProtect ? "Actief" : "Niet actief"));
		printRow("Batterij laden geblokkeerd", ($keepChargersOff ? "Actief" : "Niet actief"));
		printRow("Batterij ontladen geblokkeerd", ($forceBaseloadNull ? "Actief" : "Niet actief"));
		echo ' '.PHP_EOL;
		
// === Print additional debugMsg
		echo ' -/- DebugMsg'.PHP_EOL;
		if (!empty($GLOBALS['debugBuffer'])) {
			foreach ($GLOBALS['debugBuffer'] as $line) {
			echo '  ~~ '.$line.''.PHP_EOL;
			}
		} else {
			echo '  ~~ Geen berichten'.PHP_EOL;	
		}
		
		echo ' '.PHP_EOL;
		echo '  ---------------------------------------------------'.PHP_EOL;
		echo '  --                     The End                   --'.PHP_EOL;
		echo '  ---------------------------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;
	}

// = WritePiJson
	if(!$isManualRun) {
		$varsPiChanged = $varsPiChanged ?? false;
		if ($varsPiChanged) {
			writePiJson($varsFile, $vars);
		}
	
// = Global WriteJson
	$varsChanged = $varsChanged ?? false;
		if ($varsChanged) {
			writeJsonLocked($varsFile, $vars);
		}
		
// = Charger Lock WriteJson
	$lockChanged = $lockChanged ?? false;
		if ($lockChanged) {
			writeJsonLocked($chargerLockFile, $chargerLockFileVars);
		}
		
	}
?>
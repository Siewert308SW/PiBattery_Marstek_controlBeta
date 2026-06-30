<?php
//													     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                       Dashboard snapshot                      //
// **************************************************************//
//

	$displayFile = '/home/siewert/domoticz/www/display.json';
	$prevDisplay = file_exists($displayFile) ? json_decode(file_get_contents($displayFile), true) : [];

// = -------------------------------------------------
// = piBattery toestand / vermogen / tijd
// = -------------------------------------------------
	if ($hwChargersUsage > 10 && $batteryPct < 100) {
		$piState      = 'laden';
		$piPower      = round($hwChargersUsage);
		$piPowerLabel = 'laden';
		$piTime       = $realChargeTime ?? '00:00';
		$piTimeLabel  = 'Tijd tot vol';
	} elseif ($hwChargersUsage > 10 && $batteryPct >= 100) {
		$piState      = 'standby';
		$piPower      = round($hwChargersUsage);
		$piPowerLabel = 'calibratie';
		$piTime       = '00:00';
		$piTimeLabel  = '';
	} elseif ($hwInvsReturn < 0 && !$baseloadIdle) {
		$piState      = 'ontladen';
		$piPower      = abs(round($hwInvsReturn));
		$piPowerLabel = 'injectie';
		$piTime       = $realDischargeTime ?? '00:00';
		$piTimeLabel  = 'Tijd tot leeg';
	} elseif ($hwInvsReturn < 0 && $baseloadIdle) {
		$piState      = 'ontladen';
		$piPower      = abs(round($hwInvsReturn));
		$piPowerLabel = 'injectie idle';
		$piTime       = $realDischargeTime ?? '00:00';
		$piTimeLabel  = 'Tijd tot leeg';
	} else {
		$piState      = 'standby';
		$piPower      = 0;
		$piPowerLabel = '';
		$piTime       = '00:00';
		$piTimeLabel  = '';
	}

// = -------------------------------------------------
// = Marstek toestand / vermogen / tijd
// = -------------------------------------------------
	if ($hwMarstekUsage > 10 && $marstekSoc < 100) {
		$maState      = 'laden';
		$maPower      = round($hwMarstekUsage);
		$maPowerLabel = 'laden';
		$maTime       = $realMarstekChargeTime ?? '00:00';
		$maTimeLabel  = 'Tijd tot vol';
	} elseif ($hwMarstekReturn < 0 && !$baseloadIdle) {
		$maState      = 'ontladen';
		$maPower      = abs(round($hwMarstekReturn));
		$maPowerLabel = 'injectie';
		$maTime       = $realMarstekDischargeTime ?? '00:00';
		$maTimeLabel  = 'Tijd tot leeg';
	} elseif ($hwMarstekReturn < 0 && $baseloadIdle) {
		$maState      = 'ontladen';
		$maPower      = abs(round($hwMarstekReturn));
		$maPowerLabel = 'injectie idle';
		$maTime       = $realMarstekDischargeTime ?? '00:00';
		$maTimeLabel  = 'Tijd tot leeg';
	} else {
		$maState      = 'standby';
		$maPower      = 0;
		$maPowerLabel = '';
		$maTime       = '00:00';
		$maTimeLabel  = '';
	}

// = -------------------------------------------------
// = Marstek status/errors
// = -------------------------------------------------

// = -------------------------------------------------
// = Energie-flow knooppunten
// = -------------------------------------------------
	$solarW     = ($hwSolarReturn < 0) ? abs(round($hwSolarReturn)) : 0;
	$baseloadW  = round($currentBaseload);
	$p1W        = round($hwP1Usage);
	$overschotW = ($p1W < 0) ? abs($p1W) : 0;

	$houseLoadW = max(0, round($solarW + $hwP1Usage - $hwInvsReturn - $hwMarstekReturn - $hwMarstekUsage - $hwChargersUsage));

// = accu's gecombineerd
	$accuAvailable = round($batteryAvailable + $marstekAvailable, 2);
	$accuCapacity  = ($batteryCapacitykWh + $marstekCapacitykWh);
	$accuPct       = ($accuCapacity > 0) ? round($accuAvailable / $accuCapacity * 100) : 0;
	$accuCharge    = round($hwChargersUsage + $hwMarstekUsage);
	$accuInject    = abs(round($hwInvsReturn + $hwMarstekReturn));
	if ($accuCharge >= $accuInject) {
		$accuPower = $accuCharge;
		$accuDir   = 'laden';
	} else {
		$accuPower = $accuInject;
		$accuDir   = 'ontladen';
	}

// = -------------------------------------------------
// = RTE-verlies = gemiddeld verlies van beide accu's
// = -------------------------------------------------
	$rteVerlies = round(100 - (($chargerRTE + $marstekRTE) / 2), 1);
	$pibatteryRTEVerlies = round($chargerRTE, 1);
	$marstekRTEVerlies = round($marstekRTE, 1);
	
// = -------------------------------------------------
// = Dag-totalen
// = -------------------------------------------------
	$today     = $prevDisplay['day']['today']    ?? ['pv' => 0, 'import' => 0, 'export' => 0, 'charge' => 0, 'discharge' => 0, 'verbruik' => 0, 'verbruikNetto' => 0, 'zelf' => 0];
	$baseline  = $prevDisplay['day']['baseline'] ?? null;
	$cum       = $prevDisplay['day']['cum']      ?? ['pv' => 0, 'import' => 0, 'export' => 0, 'charge' => 0, 'discharge' => 0];
	$todayDate = date('Y-m-d');

	if ($runCharger) {
		$p1All      = getHwAll($hwP1IP);
		$solarAll   = getHwAll($hwKwhIP);
		$marstekAll = getHwAll($hwMarstekIP);

		if ($p1All['total_import'] > 0 && $solarAll['total_export'] > 0) {
			$cum = [
				'pv'        => $solarAll['total_export'],
				'import'    => $p1All['total_import'],
				'export'    => $p1All['total_export'],
				'charge'    => $hwChargersTotalInput + $marstekAll['total_import'],
				'discharge' => $hwInvTotal + $marstekAll['total_export'],
			];

			// = Nieuwe dag, meter-reset
			if ($baseline === null || !isset($baseline['discharge']) || ($baseline['date'] ?? '') !== $todayDate || $cum['import'] < ($baseline['import'] ?? 0)) {
				$baseline = ['date' => $todayDate] + $cum;
			}

			$pvT    = max(0, round($cum['pv']        - $baseline['pv'],        3));
			$impT   = max(0, round($cum['import']    - $baseline['import'],    3));
			$expT   = max(0, round($cum['export']    - $baseline['export'],    3));
			$chgT   = max(0, round($cum['charge']    - $baseline['charge'],    3));
			$disT   = max(0, round($cum['discharge'] - $baseline['discharge'], 3));
			$verbT  = max(0, round($pvT + $impT + $disT - $expT, 3));
			$verbTB = max(0, round($pvT + $impT + $disT - $expT - $chgT, 3));

			// = zelfvoorzienendheid
			if ($verbTB > 0) {
				$zelf = round(max(0, $verbTB - $impT) / $verbTB * 100);
			} else {
				$zelf = ($impT <= 0) ? 100 : 0;
			}

			$today = ['pv' => $pvT, 'import' => $impT, 'export' => $expT, 'charge' => $chgT, 'discharge' => $disT, 'verbruik' => $verbT, 'verbruikNetto' => $verbTB, 'zelf' => $zelf];
		}
	}

// = -------------------------------------------------
// = Various
// = -------------------------------------------------
	$hwP1Volt               = getHwP1VoltData($hwP1IP, $fase);
	
// = -------------------------------------------------
// = Snapshot samenstellen
// = -------------------------------------------------
	$display = [
		'updated' 			=> date('H:i:s'),
		'status'  			=> $baseloadIdle || ($hwSolarReturn < 0 && $accuCharge == 0 && $accuInject == 0)
								? 'PV-overschot · Standby '
								: (($accuDir == 'laden') ? 'PV-overschot · Laden  ' : 'PV-overschot · Tekort '),
		'baseloadIdle' 		=> $baseloadIdle,
		
		'pibattery' => [
			'socPct'        => round($batteryPct),
			'available'     => round($batteryAvailable, 2),
			'capacity'      => round($batteryCapacitykWh, 2),
			'piTemp'        => $invTemp,
			'state'         => $piState,
			'power'         => $piPower,
			'powerLabel'    => $piPowerLabel,
			'time'          => $piTime,
			'timeLabel'     => $piTimeLabel,
			'dischargeW'    => ($hwInvsReturn < 0) ? abs(round($hwInvsReturn)) : 0,
			'maxDischargeW' => ($ecoflowOneMaxOutput + $ecoflowTwoMaxOutput),
			'chargers'   => [
				['watt' => round($hwChargerOneUsage),   'on' => ($hwChargerOneStatus   == 'On')],
				['watt' => round($hwChargerTwoUsage),   'on' => ($hwChargerTwoStatus   == 'On')],
				['watt' => round($hwChargerThreeUsage), 'on' => ($hwChargerThreeStatus == 'On')],
				['watt' => round($hwChargerFourUsage),  'on' => ($hwChargerFourStatus  == 'On')],
			],
		],

		'marstek' => [
			'socPct'     	=> round($marstekSoc),
			'available'  	=> round($marstekAvailable, 2),
			'capacity'   	=> round($marstekCapacitykWh, 2),
			'mTemp'		 	=> round($marstekTemp),
			'state'      	=> $maState,
			'power'      	=> $maPower,
			'powerLabel' 	=> $maPowerLabel,
			'time'       	=> $maTime,
			'timeLabel'  	=> $maTimeLabel,
			'chargeW'    	=> max(0, round($hwMarstekUsage)),
			'dischargeW' 	=> ($hwMarstekReturn < 0) ? abs(round($hwMarstekReturn)) : 0,
			'maxW'       	=> $marstekChargerMax,
			'maxOutputW' 	=> $marstekMaxOutput,
		],

		'flow' => [
			'solar' 		=> $solarW,
			'home'  		=> $houseLoadW,
			'p1'    		=> $p1W,
			'accu'  		=> ['power' => $accuPower, 'dir' => $accuDir, 'socPct' => $accuPct],
		],

		'tiles' => [
			'p1'         	=> $p1W,
			'tprte'      	=> $pibatteryRTEVerlies,
			'overschot'  	=> $overschotW,
			'tmrte'      	=> $marstekRTEVerlies,
			'tpTemp'     	=> $invTemp,
			'tmTemp'     	=> round($marstekTemp),
		],

		'day' => [
			'today'    		=> $today,
			'baseline' 		=> $baseline,
			'cum'      		=> $cum,
		],

		'voltages' => [
			'p1Fase1'    	=> getHwP1VoltData($hwP1IP, 1),
			'p1Fase2' 		=> getHwP1VoltData($hwP1IP, 2),
			'p1Fase3'      	=> getHwP1VoltData($hwP1IP, 3),
		],
		
		'warnings' => [
			'faseProtect' 	=> $faseProtect,
			'bmsWakeActive' => $bmsWakeActive,
			'forceBaseloadNull' => $forceBaseloadNull,
			'systemFailure' => $systemFailure,
			'systemFailureIssue' => $systemFailureIssue,
		],
		
		'various' => [
			'sunriseTime' 	=> $sunrise,
			'sunsetTime' => $sunset,
		],
	];

	writeJsonLocked($displayFile, $display);
?>

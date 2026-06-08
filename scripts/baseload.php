<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Baseload                            //
// **************************************************************//
//																 //

// = -------------------------------------------------	
// = Active inverter setup
// = -------------------------------------------------
	$activeInverters = [];

	if ($usePiBattery) {
		$activeInverters['eco1'] = $ecoflowOneMax;
		$activeInverters['eco2'] = $ecoflowTwoMax;
	}

	if ($useMarstek) {
		$activeInverters['marstek'] = $marstekMax;
	}

	$activeMaxOutput = array_sum($activeInverters) / 10;

// = -------------------------------------------------	
// = Calculate new baseload
// = -------------------------------------------------
	$solarBuffer = ($hwSolarReturn < -500) ? ($baseloadSolarBuffer) : 0;
	
	if ($hwP1Usage < $activeMaxOutput){
		$newBaseloadRef = round(min($activeMaxOutput, max(0, ($hwP1Usage + $currentBaseload + $solarBuffer))) * 10);
	} elseif ($hwP1Usage >= $activeMaxOutput){
		$newBaseloadRef = round($activeMaxOutput * 10);
	}

	$newBaseload = floor(($newBaseloadRef) / 10) * 10;
	
// = -------------------------------------------------
// = Idle: Keep inverters idle x minutes after injection stops 
// = -------------------------------------------------
	$baseloadIdle 			= false;
	$baseloadIdleOverride 	= false;
	
// === If newBaseload drops to 0 but idle timer still active, keep minimal injection for x minutes
	if ($newBaseload <= ($ecoflowMinOutput * 10) && $currentTimestamp < $baseloadIdleUntil) {
		$newBaseload = ($ecoflowMinOutput * 10);
		$baseloadIdle = true;
		debugMsg('Idle actief: omvormers op minimaal vermogen (' . $ecoflowMinOutput . 'W) nog ' . ($baseloadIdleUntil - $currentTimestamp) . 's');

// === Reset idle timer
	} elseif ($baseloadIdleUntil > 0) {
		$vars['baseload_idle_until'] = 0;
		$baseloadIdleUntil = 0;
		$varsChanged = true;

// === Start idle timer
	} elseif ($usePiBattery && $newBaseload <= ($ecoflowMinOutput * 10) && $currentTimestamp >= $baseloadIdleUntil && $oldBaseload > $ecoflowMinOutput) {
		$vars['baseload_idle_until'] = $currentTimestamp + $baseloadIdleTimeout;
		$baseloadIdleUntil = $vars['baseload_idle_until'];
		$baseloadIdle = true;
		$newBaseload = ($ecoflowMinOutput * 10);
		$varsChanged = true;
		debugMsg('Idle timer gestart: omvormers blijven ' . $baseloadIdleTimeout . 's op minimaal vermogen');
		
	} elseif (!$usePiBattery && $newBaseload <= ($ecoflowMinOutput * 10) && $currentTimestamp >= $baseloadIdleUntil && $oldBaseload > $ecoflowMinOutput) {
		$vars['baseload_idle_until'] = $currentTimestamp + 60;
		$baseloadIdleUntil = $vars['baseload_idle_until'];
		$baseloadIdle = true;
		$newBaseload = ($ecoflowMinOutput * 10);
		$varsChanged = true;
		debugMsg('Idle timer gestart: omvormers blijven ' . $baseloadIdleTimeout . 's op minimaal vermogen');
	}

// = -------------------------------------------------	
// = Distribute baseload across active inverters
// = -------------------------------------------------
	$remainingLoad       = $newBaseload;
	$activeCount         = count($activeInverters);
	$invOneBaseload      = 0;
	$invTwoBaseload      = 0;
	$marstekBaseload     = 0;

	if ($activeCount > 0) {
		$targetPerInverter = round(($remainingLoad / $activeCount) / 10) * 10;

		if ($usePiBattery) {
			$invOneBaseload = min($targetPerInverter, $ecoflowOneMax);
			$invTwoBaseload = min($targetPerInverter, $ecoflowTwoMax);
		}

		if ($useMarstek) {
			$marstekBaseload = min($targetPerInverter, $marstekMax);
		}

		$distributed   = $invOneBaseload + $invTwoBaseload + $marstekBaseload;
		$remainingLoad -= $distributed;

// === Distribute remainder
		if ($remainingLoad > 0 && $usePiBattery){
			$add = min($remainingLoad, ($ecoflowOneMax - $invOneBaseload));
			$invOneBaseload += $add;
			$remainingLoad  -= $add;
		}

		if ($remainingLoad > 0 && $usePiBattery){
			$add = min($remainingLoad, ($ecoflowTwoMax - $invTwoBaseload));
			$invTwoBaseload += $add;
			$remainingLoad  -= $add;
		}

		if ($remainingLoad > 0 && $useMarstek){
			$add = min($remainingLoad, ($marstekMax - $marstekBaseload));
			$marstekBaseload += $add;
			$remainingLoad   -= $add;
		}
	}
	
// = -------------------------------------------------	
// = Baseload failsaves
// = -------------------------------------------------
	$forceBaseloadNull = false;
	
// === Set baseload to null when charging
	if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On' || $hwMarstekUsage > 0) {
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		if ($debug == 'yes' && $isManualRun){
			if ($hwMarstekUsage >= 1) {
			debugMsg('Ontladen geblokkeerd: Marstek is aan het opladen');
			} else {			
			debugMsg('Ontladen geblokkeerd: piBatterij is aan het opladen');
			}
		}
	}
	
// ==== Set baseload to null if inverters are getting hot	
	if ($invOneTemp >= $ecoflowMaxInvTemp || $invTwoTemp >= $ecoflowMaxInvTemp) {
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormers zijn te warm');
		}
	}
	
// === Set baseload to null when battery is empty
	if(!$usePiBattery){
		if ($debug == 'yes' && $isManualRun){
			debugMsg('piBattery ontladen geblokkeerd: Batterij is leeg');
		}
		
		if (!isset($vars['piBattery_empty'])) {	
			$vars['piBattery_empty'] = true;
			$varsChanged = true;
		}
			
	}

	if(!$useMarstek){
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Marstek ontladen geblokkeerd: Marstek is leeg');
		}
		
		if (!isset($vars['marstek_empty'])) {	
			$vars['marstek_empty'] = true;
			$varsChanged = true;
		}
			
	}

	if(!$usePiBattery && !$useMarstek){
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Batterijen zijn leeg');
		}
	}
	
	if(!$usePiBattery && $batteryPct > ($batteryMinimum + 10) && isset($vars['piBattery_empty'])){
		unset($vars['piBattery_empty']);
		$varsChanged = true;
	}
	
	if(!$useMarstek && $marstekSoc > ($batteryMinimum + 10) && isset($vars['marstek_empty'])){
		unset($vars['marstek_empty']);
		$varsChanged = true;
	}

// === Set baseload to null when battery calibration is still running
	if (isset($vars['charge_loss_calculation']) || isset($vars['battery_awaitingCalibration']) || $batteryPct > 100.00) {
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		if ($debug == 'yes' && $isManualRun){
		debugMsg('Ontladen geblokkeerd: Batterij calibratie moet nog worden uitgevoerd');
		}
	}
		
// === Set baseload to null when it's Winter break
	if ($isWinter && $currentTime >= $sunriseLate && $currentTime <= $sunsetEarly) {
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		debugMsg("Ontladen geblokkeerd tussen {$sunriseLate} & {$sunsetEarly}");
	}

// === Set baseload to null if inverters have to inject lower then then can handle
	if ($newBaseload >= 0 && $newBaseload <= ($ecoflowMinOutput * 10) && $hwChargerUsage == 0 && $baseloadIdle == false && $currentTimestamp >= $baseloadIdleUntil) {
		$forceBaseloadNull = true;
		debugMsg('Ontladen geblokkeerd: Vraag is minder dan minimale ontlading');
	}

// Set Baseload null when inverters aren't online
	if ($hwInvOneStatus == 'Off' || $hwInvTwoStatus == 'Off' || $hwMarstekStatus == 'Off'){
		$forceBaseloadNull 	  = true;
		$baseloadIdleOverride = true;
		
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormers sockets zijn uitgeschakeld');
		}
	}
	
	if ($forceBaseloadNull == true && $baseloadIdleOverride == true) {
		$newBaseload = 0;
		$invOneBaseload  = 0;
		$invTwoBaseload  = 0;
		$marstekBaseload = 0;
	}
	
// = -------------------------------------------------	
// = Check if baseload needs to be updated
// = -------------------------------------------------
	$updateNeeded = false;
	$delta = abs($newBaseload - abs($hwInvReturn * 10));
	
	if($hwP1Usage > 0){
		$updateNeeded = ($delta > ($baseloadPosDelta * 10));
	} elseif($hwP1Usage <= 0){
		$updateNeeded = ($delta > ($baseloadNegDelta * 10));
	}
	
// = -------------------------------------------------	
// = Update baseload
// = -------------------------------------------------
	if (!$isManualRun && $forceBaseloadNull == false && $updateNeeded == true) {

		$ecoflow->setDeviceFunction($ecoflowOneSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invOneBaseload]);
		sleep(2);
		$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invTwoBaseload]);
		setMarstekReturn(($marstekBaseload / 10));
	
// === Set new baseload variable
		if (($newBaseload / 10) != $oldBaseload) {
			$varsChanged = true;
			$vars['oldBaseload'] = ($newBaseload / 10);
			if (($newBaseload / 10) == 0) {
			$vars['invInjection'] = false;
			} else {
			$vars['invInjection'] = true;	
			}
		}

// === Force baseload null #failsave
	} elseif (!$isManualRun && $forceBaseloadNull == true && $baseloadIdle == false && $hwInvReturn < 0) {	
		$ecoflow->setDeviceFunction($ecoflowOneSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		sleep(2);
		$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		setMarstekReturn(0);
		
// === Reset baseload variable				
		if ($oldBaseload != 0) {
			$varsChanged = true;
			$vars['oldBaseload'] = 0;
			$vars['invInjection'] = false;
			$vars['marstek_force_mode'] = '';
		}
	}

?>
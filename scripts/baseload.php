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

	$totalMaxOutput = array_sum($activeInverters) / 10;

// = -------------------------------------------------	
// = Calculate new baseload
// = -------------------------------------------------
	if ($hwP1Usage < $totalMaxOutput){
		$newBaseloadRef = round(min($totalMaxOutput, max(0, ($hwP1Usage + $currentBaseload + 5))) * 10);
	} elseif ($hwP1Usage >= $totalMaxOutput){
		$newBaseloadRef = round($totalMaxOutput * 10);
	}
	
	$newBaseload = floor(($newBaseloadRef) / 10) * 10;

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
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Batterij is aan het opladen');
		}
	}
	
// ==== Set baseload to null if inverters are getting hot	
	if ($invOneTemp >= $ecoflowMaxInvTemp) {
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormer 1 te warm');
		}
	}

	if ($invTwoTemp >= $ecoflowMaxInvTemp) {
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormer 2 te warm');
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
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Batterijen zijn leeg');
		}
	}
	
	if(!$usePiBattery && $batteryPct > 35 && isset($vars['piBattery_empty'])){
		unset($vars['piBattery_empty']);
		$varsChanged = true;
	}
	
	if(!$useMarstek && $marstekBatSoc > 35 && isset($vars['marstek_empty'])){
		unset($vars['marstek_empty']);
		$varsChanged = true;
	}

// === Set baseload to null when battery calibration is still running
	if (isset($vars['charge_loss_calculation']) || isset($vars['battery_awaitingCalibration']) || $batteryPct > 100.00) {
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
		debugMsg('Ontladen geblokkeerd: Batterij calibratie moet nog worden uitgevoerd');
		}
	}
		
// === Set baseload to null when it's Winter break
	if ($isWinter && $currentTime >= $sunriseLate && $currentTime <= $sunsetEarly) {
		$forceBaseloadNull = true;
		debugMsg("Ontladen geblokkeerd tussen {$sunriseLate} & {$sunsetEarly}");
	}

// === Set baseload to null if inverters have to inject lower then then can handle
	if ($newBaseload >= 0 && $newBaseload <= ($ecoflowMinOutput * 10) && $hwChargerUsage == 0) {
		$forceBaseloadNull = true;
		debugMsg('Ontladen geblokkeerd: Vraag is minder dan minimale ontlading');
	}

// Set Baseload null when inverters aren't online
	if ($hwInvOneStatus == 'Off' || $hwInvTwoStatus == 'Off' || $hwMarstekStatus == 'Off'){
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormers sockets zijn uitgeschakeld');
		}
	}
	
	if ($forceBaseloadNull == true) {
		$newBaseload = 0;
		$invOneBaseload  = 0;
		$invTwoBaseload  = 0;
		$marstekBaseload = 0;
	}
	
// = -------------------------------------------------	
// = Check if baseload needs to be updated
// = -------------------------------------------------
	$updateNeeded = false;
	//$updateAllowed = true;
	
	$delta = abs($newBaseload - abs($hwInvReturn * 10));

	if ($forceBaseloadNull == false) {
		if($hwP1Usage > 0){
		$updateNeeded = ($delta > (10 * 10));
		} elseif($hwP1Usage <= 0){
		$updateNeeded = ($delta > (20 * 10));
		}
	}
	
// = -------------------------------------------------	
// = Update baseload
// = -------------------------------------------------
	if (!$isManualRun && $forceBaseloadNull == false && $updateNeeded == true) {

		$ecoflow->setDeviceFunction($ecoflowOneSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invOneBaseload]);
		sleep(3);
		$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invTwoBaseload]);
		//sleep(1);
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
	} elseif ((!$isManualRun && $forceBaseloadNull == true) && ($hwInvReturn < 0 && $oldBaseload != 0)) {	

		if ($hwInvReturn < 0) {
		$ecoflow->setDeviceFunction($ecoflowOneSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		sleep(3);
		$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		//sleep(1);
		setMarstekReturn(0);
		}
		
// === Reset baseload variable				
		if ($oldBaseload != 0) {
			$varsChanged = true;
			$vars['oldBaseload'] = 0;
			$vars['invInjection'] = false;
			$vars['marstek_force_mode'] = '';
		}
	}

?>
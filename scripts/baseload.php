<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Baseload                            //
// **************************************************************//
//																 //

// = -------------------------------------------------	
// = Active inverter setup $marstekState != 'idle' && $battery_allowed == true
// = -------------------------------------------------
	$useMarstek = ($marstekBatSoc > 16 && $marstekBatMode == 'Passive' && $battery_allowed == true && $hwMarstekStatus == 'On');
	
	$ecoflowOneMax   = ($ecoflowOneMaxOutput * 10);
	$ecoflowTwoMax   = ($ecoflowTwoMaxOutput * 10);
	$marstekMax      = ($marstekMaxOutput * 10);

	$activeInverters = [
		'eco1' => $ecoflowOneMax,
		'eco2' => $ecoflowTwoMax,
	];

	if ($useMarstek) {
		$activeInverters['marstek'] = $marstekMax;
	}

	$totalMaxOutput = array_sum($activeInverters) / 10;

// = -------------------------------------------------	
// = Calculate new baseload
// = -------------------------------------------------
	if ($hwP1Usage < $totalMaxOutput){
		$newBaseloadRef = round(min($totalMaxOutput, max(0, ($hwP1Usage + $currentBaseload - $ecoflowOutputOffSet))) * 10);
	} elseif ($hwP1Usage >= $totalMaxOutput){
		$newBaseloadRef = round($totalMaxOutput * 10);
	}

	$newBaseload = floor(($newBaseloadRef) / 10) * 10;

	//debugMsg("NewBaseload: {$newBaseload}");

// = -------------------------------------------------	
// = Distribute baseload across active inverters
// = -------------------------------------------------
	$remainingLoad       = $newBaseload;
	$invOneBaseload      = 0;
	$invTwoBaseload      = 0;
	$marstekBaseload     = 0;

	$activeCount         = count($activeInverters);
	$targetPerInverter   = round(($remainingLoad / $activeCount) / 10) * 10;

	$invOneBaseload = min($targetPerInverter, $ecoflowOneMax);
	$invTwoBaseload = min($targetPerInverter, $ecoflowTwoMax);

	if ($useMarstek) {
		$marstekBaseload = min($targetPerInverter, $marstekMax);
	}

	$distributed   = $invOneBaseload + $invTwoBaseload + $marstekBaseload;
	$remainingLoad -= $distributed;

// === Distribute remainder
	if ($remainingLoad > 0){
		$add = min($remainingLoad, ($ecoflowOneMax - $invOneBaseload));
		$invOneBaseload += $add;
		$remainingLoad  -= $add;
	}

	if ($remainingLoad > 0){
		$add = min($remainingLoad, ($ecoflowTwoMax - $invTwoBaseload));
		$invTwoBaseload += $add;
		$remainingLoad  -= $add;
	}

	if ($remainingLoad > 0 && $useMarstek){
		$add = min($remainingLoad, ($marstekMax - $marstekBaseload));
		$marstekBaseload += $add;
		$remainingLoad   -= $add;
	}

	//debugMsg("InvOneBaseload: {$invOneBaseload}");
	//debugMsg("InvTwoBaseload: {$invTwoBaseload}");
	//debugMsg("MarstekBaseload: {$marstekBaseload}");
	//$totaal = (($invOneBaseload + $invTwoBaseload + $marstekBaseload) / 10);
	//debugMsg("Totale Baseload: {$totaal}");
	
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
	if ($pvAvInputVoltage < $batteryVoltMin || $batteryPct <= $batteryMinimum) {
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Batterij is leeg');
		}
		
		if (!isset($vars['battery_empty'])) {	
			$vars['battery_empty'] = true;
			$varsChanged = true;
		}
			
	}
	
	//if ($pvAvInputVoltage >= $batteryVoltTrigger && isset($vars['battery_empty'])) {
	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On' || $hwChargerFourStatus == 'On') && ($batteryPct > 30 && isset($vars['battery_empty']))) {
	
	//if ($batteryPct > $batteryMinimum && isset($vars['battery_empty'])) {		
		//$forceBaseloadNull = true;
		
		unset($vars['battery_empty']);
		$varsChanged = true;
	}
		
// Set Baseload null when inverters aren't online
	if ($hwInvOneStatus == 'Off' || $hwInvTwoStatus == 'Off'){
		$forceBaseloadNull = true;
		if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Omvormers zijn offline');
		}
	}

// === Set baseload to null when battery calibration is still running || isset($vars['battery_calibrated'])  || $battery_allowed == false || $batteryPct > 100.00
		if (isset($vars['charge_loss_calculation'])) {
			$forceBaseloadNull = true;
			if ($debug == 'yes' && $isManualRun){
			debugMsg('Ontladen geblokkeerd: Batterij calibratie moet worden uitgevoerd');
			}
		}
		
// === Set baseload to null when it's Winter break
	if ($isWinter && $currentTime >= $sunriseLate && $currentTime <= $sunsetEarly) {
		$forceBaseloadNull = true;
		debugMsg("Ontladen geblokkeerd tussen {$sunriseLate} & {$sunsetEarly}");
	}

// === Set baseload to null if inverters have to inject lower then then can handle
	if ($newBaseload >= 0 && $newBaseload <= ($ecoflowMinOutput * 10)) {
		$forceBaseloadNull = true;
		debugMsg('Ontladen geblokkeerd: Vraag is minder dan minimale ontlading');
	}

// === Set baseload to null if marstek battery is not in Passive Mode
	if ($marstekBatMode == 'Auto') {
		$forceBaseloadNull = true;
		debugMsg('Ontladen geblokkeerd: Marstek in AUTO Mode');
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
		
	$delta = abs($newBaseload - abs($hwInvReturn * 10));

	if ($forceBaseloadNull == false) {
		$updateNeeded = ($delta > ($baseloadDelta * 10));
	}
	
	//if ($forceBaseloadNull == false && $hwP1Usage >= 0) {
	//	$updateNeeded = ($delta > ($baseloadDelta * 10));
	//} elseif ($forceBaseloadNull == false && $hwP1Usage < 0) {
	//	$updateNeeded = ($delta > (10 * 10));
	//}
	
// = -------------------------------------------------	
// = Update baseload
// = -------------------------------------------------
	if (!$isManualRun && $forceBaseloadNull == false && $updateNeeded == true) {

		$ecoflow->setDeviceFunction($ecoflowOneSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invOneBaseload]);
		sleep(3);
		$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber,'WN511_SET_PERMANENT_WATTS_PACK',['permanent_watts' => $invTwoBaseload]);
		sleep(2);
		setMarstekReturn($useMarstek ? ($marstekBaseload / 10) : 0);
		
// === Set new baseload variable
		if (($newBaseload / 10) != $oldBaseload) {
			$varsChanged = true;
			$vars['oldBaseload'] = ($newBaseload / 10);
		}


// === Force baseload null #failsave
	} elseif (!$isManualRun && $forceBaseloadNull == true && $hwInvReturn != 0) {	

		//if ($hwInvReturn != 0) {
			$ecoflow->setDeviceFunction($ecoflowOneSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
			sleep(3);
			$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
			sleep(2);
			setMarstekReturn(0);
		//}
			
// === Reset baseload variable				
		if ($oldBaseload != 0) {
			$varsChanged = true;
			$vars['oldBaseload'] = 0;
		}
	}

?>

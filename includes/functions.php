<?php
//															     //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                           Functions                           //
// **************************************************************//
//                                                               //

// = -------------------------------------------------
// = Function debugMsg
// = -------------------------------------------------
	if (!isset($GLOBALS['debugBuffer'])) {
		$GLOBALS['debugBuffer'] = [];
	}

	function debugMsg(string $message): void {
		global $debug;
		$formatted = "$message";
		$GLOBALS['debugBuffer'][] = $formatted;
	}
	
// = -------------------------------------------------	
// = Function column alignment
// = -------------------------------------------------
	function printRow($label, $value, $unit = '', $widthLabel = 33, $widthTotal = 13) {
		$label = str_pad($label, $widthLabel, ' ', STR_PAD_RIGHT);
		$rightPart = rtrim($value) . ($unit ? ' ' . ltrim($unit) : '');
		$rightPart = str_pad($rightPart, $widthTotal, ' ', STR_PAD_LEFT);
		echo '  -- ' . $label . ': ' . $rightPart . PHP_EOL;
	}

// = -------------------------------------------------	
// = Function GET HomeWizard data
// = -------------------------------------------------
	function getHwData($ip) {
		global $debug, $debugLang;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			curl_close($ch);
			return false;
		} else {
			$decoded = json_decode($result);
			$hwDataValue = round($decoded->active_power_w);
			curl_close($ch);
			return $hwDataValue;
		}
	}

// = -------------------------------------------------
// = Function GET HomeWizard (energy-socket) status
// = -------------------------------------------------
	function getHwStatus($ip) {
		global $debug, $debugLang;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".$ip."/api/v1/state");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			curl_close($ch);
			return false;
		} else {
			$decoded = json_decode($result);
			$statusBool = $decoded->power_on;
			$hwStatus = $statusBool == 1 ? 'On' : 'Off';
			curl_close($ch);
			return $hwStatus;
		}
	}

// = -------------------------------------------------
// = Function Switch HomeWizard (energy-socket) status
// = -------------------------------------------------
	function switchHwSocket($energySocket, $cmd) {
		global $debug, $debugLang;
		global $hwChargerOneIP, $hwChargerTwoIP, $hwChargerThreeIP, $hwChargerFourIP;
		global $hwEcoFlowOneIP, $hwEcoFlowTwoIP, $hwEcoFlowFanIP;

		// Bepaal IP-adres
		switch ($energySocket) {
			case 'one':
				$ip = $hwChargerOneIP;
				break;
			case 'two':
				$ip = $hwChargerTwoIP;
				break;
			case 'three':
				$ip = $hwChargerThreeIP;
				break;
			case 'four':
				$ip = $hwChargerFourIP;
				break;
			case 'invOne':
				$ip = $hwEcoFlowOneIP;
				break;
			case 'invTwo':
				$ip = $hwEcoFlowTwoIP;
				break;
			default:
			
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			debugMsg('Onbekend energySocket: '.$energySocket.'!');
				return;
		}

		$currentStatus = getHwStatus($ip);

		if (
			($cmd === 'On' && $currentStatus === 'On') ||
			($cmd === 'Off' && $currentStatus === 'Off')
		) {
			return;
		}

		$url = "http://$ip/api/v1/state";
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
		]);

		$cmdJson = ($cmd === 'On') ? 'true' : 'false';
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{"power_on": '.$cmdJson.'}');

		curl_exec($ch);
		curl_close($ch);
	}

// = -------------------------------------------------
// = Function GET HomeWizard Total Output Data
// = -------------------------------------------------
	function getHwTotalOutputData($ip) {
		global $debug, $debugLang;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			curl_close($ch);
			return false;
		} else {
			$decoded = json_decode($result);
			$HwTotalOutputValue = round($decoded->total_power_export_kwh, 3);
			curl_close($ch);
			return $HwTotalOutputValue;
		}
	}
	
// = -------------------------------------------------
// = Function GET HomeWizard Total Input Data
// = -------------------------------------------------
	function getHwTotalInputData($ip) {
		global $debug, $debugLang;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			curl_close($ch);
			return false;
		} else {
			$decoded = json_decode($result);
			$value = round($decoded->total_power_import_kwh, 3);
			curl_close($ch);
			return $value;
		}
	}
	
// = -------------------------------------------------	
// = Function GET HomeWizard P1 fase data
// = -------------------------------------------------
	function getHwP1FaseData($ip, $fase) {
		global $debug, $debugLang;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		if (curl_errno($ch)) { 
			debugMsg('Kan geen gegevens ophalen van Homewizard: '.$ip.'!');
			curl_close($ch);
			return false;
		} else {
			$decoded = json_decode($result);
			switch ($fase) {
				case 1:
					$HwP1FaseValue = round($decoded->active_power_l1_w, 3);
					break;
				case 2:
					$HwP1FaseValue = round($decoded->active_power_l2_w, 3);
					break;
				case 3:
					$HwP1FaseValue = round($decoded->active_power_l3_w, 3);
					break;
				default:
					$HwP1FaseValue = false;
					break;
			}
			curl_close($ch);
			return $HwP1FaseValue;
		}
	}

// = -------------------------------------------------	
// = Function to convert time in decimals to realTime
// = -------------------------------------------------
	function convertTime($dec)
	{
		$seconds = ($dec * 3600);
		$hours = floor($dec);
		$seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);
		$seconds -= $minutes * 60;
		return lz($hours).":".lz($minutes)."";
	}

// = -------------------------------------------------
// = lz = leading zero
// = -------------------------------------------------
	function lz($num)
	{
		return (strlen($num) < 2) ? "0{$num}" : $num;
	}

// = -------------------------------------------------
// = Function Update Domoticz Device
// = -------------------------------------------------

	function UpdateDomoticzDevice($idx,$cmd) {
	  global $domoticzIP;
	  global $batterySOCIDX;
	  global $marstekSOCIDX;
	  global $batteryVoltageIDX;
	  global $batteryAvailIDX;
	  global $marstekAvailIDX;
	  global $batteryChargeTimeIDX;
	  global $batteryDischargeTimeIDX;
	  global $marstekChargeTimeIDX;
	  global $marstekDischargeTimeIDX;
	  global $inputCounterIDX;
	  global $outputCounterIDX;
	  global $marstekInputCounterIDX;
	  global $marstekOutputCounterIDX;
	  global $pvCounterIDX;
	  //global $ecoFlowTempIDX;
	  global $batteryRTEIDX;
	  global $marstekRTEIDX;
	  
	  $reply = ['status' => 'ERROR'];
	  
	  if ($idx == $marstekInputCounterIDX || $idx == $marstekOutputCounterIDX || $idx == $inputCounterIDX || $idx == $outputCounterIDX || $idx == $batterySOCIDX || $idx == $marstekSOCIDX || $idx == $batteryVoltageIDX || $idx == $pvCounterIDX || /*$idx == $ecoFlowTempIDX || */$idx == $batteryRTEIDX || $idx == $marstekRTEIDX){
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd.';0'),true);
	  }
	  
	  if ($idx == $marstekChargeTimeIDX || $idx == $marstekDischargeTimeIDX || $idx == $batteryChargeTimeIDX || $idx == $batteryDischargeTimeIDX || $idx == $batteryAvailIDX || $idx == $marstekAvailIDX){
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd.''),true);
	  }
	  
	  if (($reply['status'] ?? '') == 'OK') $reply='OK'; else $reply='ERROR';
	  return $reply;
	}

// = -------------------------------------------------
// = Function get/compare and update Domoticz data
// = -------------------------------------------------
	function UpdateDomoticzDeviceIfChanged($idx, $cmd) {
		global $domoticzStateFile;

		$cmd = (string)$cmd;

		$state = [];
		if (file_exists($domoticzStateFile)) {
			$state = json_decode(file_get_contents($domoticzStateFile), true);
			if (!is_array($state)) $state = [];
		}

		if (($state[$idx] ?? null) === $cmd) {
			return 'SKIP';
		}

		$reply = UpdateDomoticzDevice($idx, $cmd);

		if ($reply == 'OK') {
			$state[$idx] = $cmd;
			writeJsonLocked($domoticzStateFile, $state);
		}

		return $reply;
	}
	
// = -------------------------------------------------	
// = Send PiBattery/Marstek battery status to Domoticz
// = -------------------------------------------------
	function sendBatteryStatusToDomoticz() {
		$domoticzUrl   = 'http://192.168.178.7:8080';
		global $batteryPct;
		global $marstekBatSoc;
		global $marstekMaxOutput;
		global $ecoflowOneMaxOutput;
		global $ecoflowTwoMaxOutput;
		global $usePiBattery;
		global $useMarstek;
		global $batteryMinimum;
		global $marstekMinimum;
		
		$totalDischargeMarstek  = 0;
		$totalDischargePiBattery  = 0;
		$dischargeAvailable = 0;
		$totalPibatteryPct = round(($batteryPct), 0);
		$totalMarstekPct = round(($marstekBatSoc), 0);
		
// === Calculate total injection		
		if ($marstekBatSoc > 45) {
			$totalDischargeMarstek = $marstekMaxOutput;
		}

		if ($batteryPct > 45) {
			$totalDischargePiBattery = $ecoflowOneMaxOutput + $ecoflowTwoMaxOutput;
		}


		if ($usePiBattery && $useMarstek) {
			$dischargeAvailable = ($totalDischargeMarstek + $totalDischargePiBattery);
		} elseif ($usePiBattery && !$useMarstek) {
			$dischargeAvailable = ($totalDischargePiBattery);			
		} elseif (!$usePiBattery && $useMarstek) {
			$dischargeAvailable = ($totalDischargeMarstek);			
		} elseif (!$usePiBattery && !$useMarstek) {
			$dischargeAvailable = 0;			
		}
		
		$updates = [
			[
				'name'  => 'PiBattery_BatteryPct',
				'type'  => 0,
				'value' => (string)$totalPibatteryPct,
			],
			[
				'name'  => 'Marstek_BatteryPct',
				'type'  => 0,
				'value' => (string)$totalMarstekPct,
			],
			[
				'name'  => 'PiBattery_DischargeAvailable',
				'type'  => 0, // Integer
				'value' => (string)$dischargeAvailable,
			],
		];

		foreach ($updates as $update) {
			$url = $domoticzUrl . '/json.htm?type=command&param=updateuservariable'
				. '&vname=' . rawurlencode($update['name'])
				. '&vtype=' . $update['type']
				. '&vvalue=' . rawurlencode($update['value']);

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 3,
				CURLOPT_TIMEOUT        => 5,
				CURLOPT_FAILONERROR    => false,
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error    = curl_error($ch);

			curl_close($ch);

			$json = json_decode($response, true);

		}
	}

// = -------------------------------------------------	
// = Marstek Set Return Power
// = -------------------------------------------------
	function setMarstekReturn($watts) {
		global $marstekIP;
		global $vars;
		global $varsChanged;

		$marstek = new MarstekModbus($marstekIP);

		if (($vars['marstek_force_mode'] ?? '') !== 'discharge') {

			$vars['marstek_force_mode'] = 'discharge';
			$varsChanged = true;

			return $marstek->startDischargePower((int)$watts);
		}

		return $marstek->setDischargePower((int)$watts);
	}
	
// = -------------------------------------------------	
// = Marstek Set Charge Power
// = -------------------------------------------------
	function setMarstekUsage($watts) {
		global $marstekIP;
		global $vars;
		global $varsChanged;

		$marstek = new MarstekModbus($marstekIP);

		if (($vars['marstek_force_mode'] ?? '') !== 'charge') {

			$vars['marstek_force_mode'] = 'charge';
			$varsChanged = true;

			return $marstek->startChargePower((int)$watts);
		}

		return $marstek->setChargePower((int)$watts);
	}
	
// = -------------------------------------------------
// = Function writeJsonLocked
// = -------------------------------------------------
	function writeJsonLocked(string $filename, array $data): void {
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
?>
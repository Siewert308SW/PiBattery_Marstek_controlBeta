<?php
// = -------------------------------------------------
// = Marstek get state helper
// = -------------------------------------------------
		$cacheFile = $piBatteryPath . 'data/marstek_state.json';
		$cacheTtl  = 5;

		if (!$forceRefresh && file_exists($cacheFile)) {
			$cached = json_decode(file_get_contents($cacheFile), true);

			if (
				is_array($cached) &&
				isset($cached['timeStamp']) &&
				(time() - (int)$cached['timeStamp']) < $cacheTtl
			) {
				return $cached;
			}
		}

		$prev = [];
		if (file_exists($cacheFile)) {
			$prev = json_decode(file_get_contents($cacheFile), true);
			if (!is_array($prev)) {
				$prev = [];
			}
		}

		$modeResponse   = marstekUdpCall($marstekIP, $marstekPort, 'ES.GetMode', 101, 2);
		usleep(300000);
		$statusResponse = marstekUdpCall($marstekIP, $marstekPort, 'ES.GetStatus', 102, 2);

		$modeResult   = $modeResponse['result'] ?? [];
		$statusResult = $statusResponse['result'] ?? [];

		$marstekMode = $modeResult['mode'] ?? ($prev['marstekMode'] ?? 'unknown');

		$marstekSoc = null;
		if (isset($statusResult['bat_soc']) && is_numeric($statusResult['bat_soc'])) {
			$marstekSoc = round((float)$statusResult['bat_soc'], 1);
		} elseif (isset($modeResult['bat_soc']) && is_numeric($modeResult['bat_soc'])) {
			$marstekSoc = round((float)$modeResult['bat_soc'], 1);
		} else {
			$marstekSoc = $prev['marstekSoc'] ?? null;
		}

		if ($hwMarstekSocket >= 0 && $hwMarstekSocket < 10) {
			$marstekState = 'idle';
		} elseif ($hwMarstekSocket >= 10) {
			$marstekState = 'charging';
		} elseif ($hwMarstekSocket < 0) {
			$marstekState = 'discharging';
		} else {
			$marstekState = 'unknown';
		}

		$data = [
			'timeStamp'    => time(),
			'dateNow'      => date('Y-m-d H:i:s'),
			'marstekMode'  => $marstekMode,
			'marstekState' => $marstekState,
			'marstekSoc'   => $marstekSoc
		];

		writeJsonLocked($cacheFile, $data);
?>
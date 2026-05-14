<?php
//															     //
// **************************************************************//
//                    Marstek Modbus Class                       //
//                        No need to edit                        //
// **************************************************************//
//                                                               //

class MarstekModbus
{
	private string $ip;
	private int $port;
	private int $unit;
	private int $timeout;
	private $socket = null;
	private int $transaction = 1;

// = -------------------------------------------------
// = Enable Control
// = -------------------------------------------------
	private function enableControl(): bool
	{
		return $this->writeHoldingRegister(42000, 21930);
	}
	
	public function __construct(string $ip, int $port = 502, int $unit = 1, int $timeout = 3)
	{
		$this->ip      = $ip;
		$this->port    = $port;
		$this->unit    = $unit;
		$this->timeout = $timeout;
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	private function connect(): bool
	{
		if (is_resource($this->socket)) {
			return true;
		}

		$this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $this->timeout);

		if (!$this->socket) {
			$this->socket = null;
			return false;
		}

		stream_set_timeout($this->socket, $this->timeout);

		return true;
	}

	private function disconnect(): void
	{
		if (is_resource($this->socket)) {
			fclose($this->socket);
		}

		$this->socket = null;
	}

	private function toSigned16(int $value): int
	{
		return ($value >= 32768) ? $value - 65536 : $value;
	}

	private function toSigned32(int $high, int $low): int
	{
		$value = ($high << 16) | $low;

		return ($value >= 2147483648) ? $value - 4294967296 : $value;
	}

	private function readHoldingRegisters(int $address, int $count = 1): ?array
	{
		if (!$this->connect()) {
			return null;
		}

		$packet =
			pack('n', $this->transaction++) .
			pack('n', 0) .
			pack('n', 6) .
			pack('C', $this->unit) .
			pack('C', 3) .
			pack('n', $address) .
			pack('n', $count);

		fwrite($this->socket, $packet);
		$response = fread($this->socket, 512);

		if (strlen($response) < 9) {
			$this->disconnect();
			return null;
		}

		$function = ord($response[7]);

		if ($function & 0x80) {
			return null;
		}

		$byteCount = ord($response[8]);
		$rawData   = substr($response, 9, $byteCount);
		$values    = [];

		for ($i = 0; $i < strlen($rawData); $i += 2) {
			$values[] = unpack('n', substr($rawData, $i, 2))[1];
		}

		return $values;
	}

	public function writeHoldingRegister(int $address, int $value): bool
	{
		if (!$this->connect()) {
			return false;
		}

		if ($value < 0) {
			$value = 65536 + $value;
		}

		$value = max(0, min(65535, $value));

		$packet =
			pack('n', $this->transaction++) .
			pack('n', 0) .
			pack('n', 6) .
			pack('C', $this->unit) .
			pack('C', 6) .
			pack('n', $address) .
			pack('n', $value);

		fwrite($this->socket, $packet);
		$response = fread($this->socket, 256);

		if (strlen($response) < 12) {
			$this->disconnect();
			return false;
		}

		$function = ord($response[7]);

		if ($function & 0x80) {
			return false;
		}

		return true;
	}

	public function isOnline(): bool
	{
		return ($this->readHoldingRegisters(34002, 1) !== null);
	}

	public function getData(): array
	{
		$data = [
			'online' => false,
		];

		// = -------------------------------------------------
		// = Battery basic data
		// = -------------------------------------------------
		// 34000 Battery Voltage  uint16 /100 V
		// 34001 Battery Current  int16  /100 A
		// 34002 Battery SOC      uint16 /10  %
		// 34003 Battery Temp     int16        °C
		// 34004 Battery State    uint16
		$bat = $this->readHoldingRegisters(34000, 5);

		if (!is_array($bat) || count($bat) < 5) {
			$this->disconnect();
			return $data;
		}

		$data['online']         = true;
		$data['batteryVoltage'] = round($bat[0] / 100, 2);
		$data['batteryCurrent'] = round($this->toSigned16($bat[1]) / 100, 2);
		$data['batterySoc']     = round($bat[2] / 10, 1);
		$data['batteryTemp']    = $this->toSigned16($bat[3]);
		$data['batteryState']   = $bat[4];

		// = -------------------------------------------------
		// = Battery Power
		// = -------------------------------------------------
		// 30001 Battery Power int16 W
		$power = $this->readHoldingRegisters(30001, 1);

		if (is_array($power) && count($power) >= 1) {
			$data['batteryPower'] = $this->toSigned16($power[0]);
		}

		// = -------------------------------------------------
		// = AC data
		// = -------------------------------------------------
		// 32200 AC Voltage   uint16 /10 V
		// 32202 AC Power     int32      W
		// 32204 AC Frequency uint16 /10 Hz
		$ac = $this->readHoldingRegisters(32200, 5);

		if (is_array($ac) && count($ac) >= 5) {
			$data['acVoltage']   = round($ac[0] / 10, 1);
			$data['acPower']     = $this->toSigned32($ac[2], $ac[3]);
			$data['acFrequency'] = round($ac[4] / 10, 1);
		}

		// = -------------------------------------------------
		// = Inverter state
		// = -------------------------------------------------
		// 35100 Inverter State
		$inverterState = $this->readHoldingRegisters(35100, 1);

		if (is_array($inverterState) && count($inverterState) >= 1) {
			$data['inverterState'] = $inverterState[0];
		}

		$this->disconnect();

		return $data;
	}

	// = -------------------------------------------------
	// = Set Charge Power
	// = -------------------------------------------------
	public function setChargePower(int $watts): bool
	{
		$watts = abs($watts);

		if (!$this->writeHoldingRegister(42020, $watts)) {
			$this->disconnect();
			return false;
		}

		$result = $this->writeHoldingRegister(42010, 1); // charge

		$this->disconnect();

		return $result;
	}

	// = -------------------------------------------------
	// = Set Discharge Power
	// = -------------------------------------------------
	public function setDischargePower(int $watts): bool
	{
		$watts = abs($watts);

		if (!$this->enableControl()) {
			$this->disconnect();
			return false;
		}

		if (!$this->writeHoldingRegister(42021, $watts)) {
			$this->disconnect();
			return false;
		}

		$result = $this->writeHoldingRegister(42010, 2); // discharge

		$this->disconnect();

		return $result;
	}

	// = -------------------------------------------------
	// = Stop Power
	// = -------------------------------------------------
	public function stopPower(): bool
	{
		$result = $this->writeHoldingRegister(42010, 0); // stop

		$this->disconnect();

		return $result;
	}
}
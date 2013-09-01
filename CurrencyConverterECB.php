<?php
/**
 * CurrencyConverterECB
 *
 * This class allows to access currency exchange rates published by the European
 * Central Bank (ECB).
 *
 * ECB link:
 * http://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html
 *
 * @author		Nikos Topulos
 * @version 	1.1.3
 * @link 		https://github.com/ntopulos/CurrencyConverterECB
 */
class CurrencyConverterECB {

	// Configuration
	private $source = 'www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

	// Basics
	private $mysqli;
	private $table;
	public $exchange_rates = array();

	/**
	 * Constructor, initializations and check if the database is up to date
	 *
	 * @param 	string 		name of the table
	 * @param	object		mysqli connection
	 * @return	void
	*/
	function __construct($table, $mysqli) {

		// Init
		$this->mysqli = $mysqli;
		$this->table = $table;

		// Check and update
		if(!$this->isUpToDate()) {
			$this->updateRates();
		}

	}

	/**
	 * Verifies whether exchange rates in the db are up to date (daily update).
	 * If true assigns rates to $this->exchange_rates.
	 * Returns true or false.
	 *
	 * @param	void
	 * @return	boolean
	*/
	private function isUpToDate() {

		$today_rates = $this->getRatesFromDB();

		if($today_rates) {
			$this->exchange_rates = $today_rates;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets currency rates from the DB.
	 *
	 * @param	date/false 		MySQL date or false (= CURDATE())
	 * @return	array/false 	array of the rates or false if no rates found
	*/
	public function getRatesFromDB($date=false) {

		// adapting date for the query
		$date = (!$date ? 'CURDATE()' : "'" .$date. "'");

		$res = $this->mysqli->query(
			"SELECT * FROM " .$this->table. "
			WHERE `exchange_rate_date` = $date");

		if($res->num_rows == 1) {

			$row = $res->fetch_assoc();

			// droping not currency column
			array_shift($row);

			// adding EUR to the array
			$row['EUR'] = 1;

			return $row;
		} else {
			return false;
		}

		$res->close();
	}

	/**
	 * Performs the conversion.
	 *
	 * @param	float
	 * @param	string
	 * @param	string
	 * @param 	date
	 * @param	int
	 * @return	float
	*/
	public function convert($amount=1, $from, $to, $date=false, $precision=2) {

		$rates = (!$date ? $this->exchange_rates : $this->getRatesFromDB($date));

		if(!$rates) {
			$this->error('no entry for '.$date);
		}

		return(round(($amount/$rates[$from])*$rates[$to], $precision));
	}

	/**
	 * Updates the rates that exist in the table.
	 *
	 * @param	void
	 * @return	void
	*/
	private function updateRates() {

		// Getting columns (within are currencies ids)
		$res = $this->mysqli->query(
			"SELECT `column_name`
				FROM information_schema.columns
				WHERE `TABLE_SCHEMA` IN (SELECT database())
				AND `table_name` = '$this->table'") or die($this->mysqli->error);

		while($row = $res->fetch_row()) {
			$db_columns[] = $row[0];
		}

		$res->close();

		// Getting last rates
		$this->downloadLatestRates();

		// Filtering (intersecting) of both arrays
		$last_rates = array_intersect_key($this->exchange_rates, array_flip($db_columns));

		// Preparing query
		$keys_str = "(`exchange_rate_date`";
		$values_str = '(NOW()';

		foreach($last_rates as $key => $value) {
			$keys_str .= ",`" .$this->mysqli->real_escape_string($key). "`";
			$values_str .= "," .floatval($value);
		}

		$keys_str .= ')';
		$values_str .= ')';

		$query = "INSERT INTO `" .$this->table. "` ".
					$keys_str. "
					VALUES ". $values_str;

		// Inserting into DB
		$this->mysqli->query($query) or $this->error('MySQL insert failed');
	}

	/**
	 * Downloads the last rates from the ECB.
	 * Assigns an associative array of currencies -> rates to exchange_rates.
	 *
	 * @param	void
	*/
	public function downloadLatestRates() {

		// curl
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL 			=> $this->source,
			CURLOPT_RETURNTRANSFER 	=> 1,
			CURLOPT_TIMEOUT			=> 2
		));

		$result = curl_exec($curl); 
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		// No HTTP error authorized
		if($http_code >= 400) {
			$this->error('HTTP status code ' . $http_code);
		}
		
		// Converting to an array
		$pattern = "{<Cube\s*currency='(\w*)'\s*rate='([\d\.]*)'/>}is";
		preg_match_all($pattern,$result,$xml_rates);
		array_shift($xml_rates);

		// Returning associative array (currencies -> rates)
		$result = array_combine($xml_rates[0], $xml_rates[1]);

		// Checking for error
		if(empty($result)) {
			$this->error('empty result');
		}

		// Adding EUR = 1
		$result = array('EUR' => 1) + $result;

		$this->exchange_rates = $result;
	}

	/**
	 * Minimalistic error handler.
	 *
	 * @param	string
	 * @return	void
	*/
	private function error($msg) {
		die('CurrencyConverterECB error: '.$msg);
	}
}
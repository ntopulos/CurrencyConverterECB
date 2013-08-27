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
 * @version 	1.0
 * @link 		
 */
class CurrencyConverterECB {

	// Configuration
	private $source = 'www.ecb.int/stats/eurofxref/eurofxref-daily.xml';
	private $table = 'currency_exchange';	// mysql table name

	// Basics
	private $mysqli;
	private $email;

	/**
	 * Constructor, initializes variables
	 *
	 * @param 	string 		name of the table
	 * @param	object		mysqli connection
	 * @param 	string 		email address in case of execution issues
	 * @return	void
	*/
	function __construct($mysqli, $email) {

		$this->mysqli = $mysqli;
		$this->email = $email;

		if(!$this->isUpToDate()) {
			echo 'must be updated';

			$this->updateRates();
		}

	}

	/**
	 * Verifies whether exchange rates in the db are up to date (daily update).
	 * Returns true or false.
	 *
	 * @param	void
	 * @return	boolean
	*/
	private function isUpToDate() {

		$res = $this->mysqli->query(
			"SELECT * FROM " .$this->table. "
			WHERE `exchange_rate_date` = CURDATE()");

		if($res->num_rows == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Updates the rates that exists in the table.
	 *
	 * @param	void
	 * @return	void
	*/
	private function updateRates() {

		// Getting columns (within are currencies ids)
		$res = $this->mysqli->query(
			"SELECT DISTINCT column_name
				FROM information_schema.columns
				WHERE `table_name` = '$this->table'");

		$arr = $res->fetch_all();
		$res->close();

		foreach($arr as $row) {
			$db_columns[] = $row[0];
		}

		// Getting last rates
		$last_rates_raw = $this->downloadLastRates();

		// Filtering (intersecting) of both arrays
		$last_rates = array_intersect_key($last_rates_raw, array_flip($db_columns));

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
		$this->mysqli->query($query) or die('CurrencyConverterECB error: MySQL insert failed');
	}

	/**
	 * Downloads the last rates from the ECB.
	 * Returns an associative array with currencies -> rates.
	 *
	 * @param	void
	 * @return	array
	*/
	public function downloadLastRates() {

		// curl
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL 			=> $this->source,
			CURLOPT_RETURNTRANSFER 	=> 1,
			CURLOPT_TIMEOUT			=> 2,
			CURLOPT_FOLLOWLOCATION => true
		));

		$result = curl_exec($curl); 
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		// No http error authorized
		if($http_code >= 400) {
			die('CurrencyConverterECB error: HTTP status code ' . $http_code);
		}

		// Converting to an array
		$pattern = "{<Cube\s*currency='(\w*)'\s*rate='([\d\.]*)'/>}is";
		preg_match_all($pattern,$result,$xml_rates);
		array_shift($xml_rates);

		// Returning associative array (currencies -> rates)
		return array_combine($xml_rates[0], $xml_rates[1]);
	}
}
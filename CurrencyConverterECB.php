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
 * This class is inspired by the work of Simon Jarvis:
 * http://www.white-hat-web-design.co.uk/articles/php-currency-conversion.php
 *
 * @author		Nikos Topulos
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

		if( $this->isUpToDate()) {
			echo 'up to date';
		} else {
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
			WHERE 'exchange_rate_date' = NOW()");

		if($res->num_rows == 1) {
			return true;
		} else {
			return false;
		}
	}

	private function updateRates() {

		// Getting colums (within we have currencies ids)
		$res = $this->mysqli->query(
			"SELECT DISTINCT column_name
			FROM information_schema.columns
			WHERE `table_name` = '$this->table'");

		$arr = $res->fetch_all();

		foreach($arr as $row) {
			$db_columns[] = $row[0];
		}

		// Getting last rates
		$last_rates = $this->downloadLastRates();

		// Filtering (intersecting) of both arrays
		$intersection = array_intersect_key($last_rates,array_flip($db_columns));

		print_r($intersection);
		// Updating DB

		$query = "INSERT INTO $this->table (link) VALUES (?)";
		$stmt = $mysqli->prepare($query);

		foreach ($array as $one) {
		$stmt ->bind_param("s", $one);
		$stmt->execute();
		}
		$stmt->close();
	}

	/**
	 * Downloads the last rates from the ECB.
	 * Returns an associative array with currencies -> rates.
	 *
	 * @param	void
	 * @return	array
	*/
	public function downloadLastRates() {

		// curl cubrid_query 	- TO DO ADD HTTP CODE MANAGEMENT
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

		// converting to array
		$pattern = "{<Cube\s*currency='(\w*)'\s*rate='([\d\.]*)'/>}is";
		preg_match_all($pattern,$result,$xml_rates);
		array_shift($xml_rates);

		return array_combine($xml_rates[0], $xml_rates[1]);
	}
}
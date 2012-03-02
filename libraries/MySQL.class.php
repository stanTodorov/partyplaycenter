<?php
/**
 * MySQL wrapper class.
 * Require PHP5
 *
 * @author           Mikhail Kyosev <mialygk@gmail.com>
 * @version          1.4
 * @copyright        2010-2011
 * @license          BSD-new
 */
class MYSQL {
	// status
	const STATUS_OK = 0;
	const STATUS_ERROR = 1;
	const STATUS_NOT_CONNECT = 2;

	// query types
	const QUERY_NUM_ROWS = 1;
	const QUERY_AFFECTED_ROWS = 2;

	public $hostname;                         // Hostname of MySQL
	public $username;                         // MySQL username
	public $password;                         // MySQL password
	public $database;                         // MySQL database name
	public $codepage;                         // Using code pages (SET NAMES ...)
	public $connport;                         // Connection port
	private $_connection;                     // Connection Identifier
	private $_result;                         // MySQL query result
	private $_errorMessages;                  // MySQL messages
	private $_status;                         // MySQL server status
	private $_countType;                      // Type of previous query
	private $_sqlCount;                       // SQL Queries Counter
	private static $_instance = null;         // Instance

	/**
	 * Construction
	 *
	 * @param string $db array for database
	 * @param bool $connect_now True for immediately connection to MySQL
	 */
	private function __construct($db = array(), $connect_now = false)
	{
		$this->_status = self::STATUS_OK;
		$this->_sqlCount = 0;

		if (!is_array($db) || count($db) == 0) {
			$this->_setStatus(self::STATUS_NOT_CONNECT);
			return;
		}

		// default standard values
		$this->hostname = 'localhost';
		$this->connport = 3306;
		$this->username = '';
		$this->password = '';
		$this->database = '';

		foreach ($db as $key => $data) {
			switch($key) {
			case 'HOSTNAME':
				$this->hostname = $data;
				break;
			case 'CONNPORT':
				$this->connport = $data;
				break;
			case 'USERNAME':
				$this->username = $data;
				break;
			case 'PASSWORD':
				$this->password = $data;
				break;
			case 'DATABASE':
				$this->database = $data;
				break;
			case 'CODEPAGE':
				$this->codepage = $data;
				break;
			default:
			}
		}

		if ($connect_now === true) {
			$this->connect();
		}
		else {
			$this->_setStatus(self::STATUS_NOT_CONNECT);
		}
	}


	/**
	 * Close connection
	 */
	function __destruct() {
		if ($this->_connection) {
			@mysql_close($this->_connection);
		}
	}


	/**
	 * Get Status of MySQL Connection
	 *
	 * @return bool false if connection is not established, true - otherwise
	 */
	public function getStatus() {
		if ($this->_status !== self::STATUS_OK) return false;
		else return true;
	}


	/**
	 * Get all errors as indexed array
	 *
	 * @return array Error messages
	 */
	public function getErrors() {
		return $this->_errorMessages;
	}


	/**
	 * establishe connection to mysql server
	 *
	 * @return bool true if connection established, otherwise return false
	 */
	public function connect($newLink = false) {
		if ($this->_connection) {
			return true;
		}

		$host = $this->hostname.':'.$this->connport;
		$user = $this->username;
		$pass = $this->password;
		$newLink = ($newLink === true) ? true : false;

		$this->_connection = @mysql_connect($host, $user, $pass, $newLink);

		if ($this->_connection == false) {
			$this->_setStatus(self::STATUS_ERROR);
			$this->_setErrorMsg("Connect: ".mysql_error());
			return false;
		}
		if (!mysql_select_db($this->database, $this->_connection)) {
			$this->_setStatus(self::STATUS_ERROR);
			$this->_setErrorMsg("Select DataBase: ".mysql_error());
			return false;
		}
		if ($this->codepage !== '') {
			if (!$this->query('SET NAMES '.$this->codepage)) {
				return false;
			}
		}
		return true;
	}


	/**
	 * Singleton design pattern method to get current instance of object
	 *
	 */
	public static function getInstance($db = array())
	{
		if (!self::$_instance) {
			self::$_instance = new self($db, true);
		}

		return self::$_instance;
	}


	/**
	 * Get Number of last inserting or selecting records
	 *
	 * @return integer Number of records
	 */
	public function getCount() {
		if ($this->_countType == self::QUERY_NUM_ROWS) {
			return mysql_num_rows($this->_result);
		}
		else if ($this->_countType == self::QUERY_AFFECTED_ROWS) {
			return mysql_affected_rows($this->_connection);
		}
		return 0;
	}


	/**
	 * Get last value of auto_inrement index, after inserting
	 *
	 * @return integer Last ID number of auto_increment, 0 if error occurs
	 */
	public function getLastID() {
		if ($this->_countType == self::QUERY_AFFECTED_ROWS) {
			return mysql_insert_id($this->_connection);
		}
		return 0;
	}


	/**
	 * Fetch rows from query output as indexed array
	 *
	 * @param mixed $result Custom MySQL Result container
	 * @return array Fetched rows
	 */
	public function getRow($result = null) {
		if ($result) {
			return mysql_fetch_row($result);
		}
		else {
			return mysql_fetch_row($this->_result);
		}
	}


	/**
	 * Fetch rows from query output as associative array
	 *
	 * @param mixed $result Custom MySQL Result container
	 * @return array Fetched rows
	 */
	public function getAssoc($result = null)
	{
		if ($result) {
			return mysql_fetch_assoc($result);
		}
		else {
			return mysql_fetch_assoc($this->_result);
		}
	}


	/**
	 * Fetch ALL rows from query output as associative array
	 *
	 * @param mixed $result Custom MySQL Result container
	 * @return array
	 */
	public function getAssocArray($result = null)
	{
		$assoc = array();
		while($tmp = $this->getAssoc($result)) {
			$assoc[] = $tmp;
		}

		return $assoc;
	}


	/**
	 * General Query to MySQL
	 * Detecting function for GetCount, SELECT, SHOW, INSERT, DELETE, REPLACE and
	 * UPDATE must in uppercase
	 *
	 * @param string $query Query string to database
	 * @return bool True if success, false otherwise
	 */
	public function query($query)
	{
		$this->_sqlCount++;
		$this->_countType = 0;
		$this->_result = mysql_query($query, $this->_connection);
		if (!$this->_result) {
			$this->_setStatus(self::STATUS_ERROR);
			$this->_setErrorMsg("Query: ".mysql_error());
			return false;
		}

		if (       (strstr($query, "SELECT") != FALSE)
			|| (strstr($query, "SHOW") != FALSE))
		{
			$this->_countType = self::QUERY_NUM_ROWS;
		}
		else if (  (strstr($query, "INSERT") != FALSE)
			|| (strstr($query, "DELETE") != FALSE)
			|| (strstr($query, "REPLACE") != FALSE)
			|| (strstr($query, "UPDATE") != FALSE) )
		{
			$this->_countType = self::QUERY_AFFECTED_ROWS;
		}
		else {
			$this->_countType = 0;
		}

		return $this->_result;
	}


	/**
	 * Update Query to MySQL
	 *
	 * @param string $table Name of Table to update
	 * @param array $data Assoc Array with field names and they values to Update
	 * @param string $where Where clause to rows affect
	 * @param bool $escaping True for escaping, false otherwise
	 * @param string $ret_query Generated Query as String for debug purpose
	 *
	 * @return bool True if success, false otherwise
	 */
	public function queryUpdate($table, $data, $where, $escaping = true, &$ret_query = null)
	{
		if (mb_strlen($where) == 0 || !is_array($data)) {
			return false; // what to override anyway?
		}

		$update = '';
		foreach ($data as $key => $val) {
			if ($escaping === true) {
				$val = $this->EscapeString($val);
			}
			if (is_null($val) || mb_strtolower($val) == 'null' || $val == '') {
				$val = 'NULL';
			}
			else if (mb_strtolower($val) == 'now()') {
				$val = 'NOW()';
			}
			else {
				$val = "'".$val."'";
			}
			$update .= " `".$key."` = ".$val.", ";
		}
		$update = rtrim($update, ", ");

		$query = "
			UPDATE `".$table."` SET
			".$update."
			WHERE ".$where."
		";

		$ret_query = $query;

		return $this->query($query);
	}


	/**
	 * Insert Query to MySQL
	 *
	 * @param string $table Name of Table to update
	 * @param array $data Assoc Array with field names and they values to insert
	 * @param bool $escaping True for escaping, false otherwise
	 * @param string $ret_query Generated Query as String for debug purpose
	 *
	 * @return bool True if success, false otherwise
	 */
	public function queryInsert($table, $data, $escaping = true, &$ret_query = null)
	{
		if (!is_array($data)) {
			return false; // what to override anyway?
		}

		$values = $keys = '';
		foreach ($data as $key => $val) {
			$keys .= "`".$key."`, ";

			if ($escaping === true) {
				$val = $this->EscapeString($val);
			}
			if (is_null($val) || mb_strtolower($val) == 'null' || $val == '') {
				$val = "''"; // Null symbol
			}
			else if (mb_strtolower($val) == 'now()') {
				$val = 'NOW()';
			}
			else {
				$val = "'".$val."'";
			}
			$values .= $val.", ";
		}
		$values = rtrim($values, ", ");
		$keys = rtrim($keys, ", ");

		$query = "
			INSERT INTO `".$table."` (
				".$keys."
			) VALUES (
				".$values."
			)
		";

		$ret_query = $query;

		return $this->query($query);
	}


	/**
	 * Escaping string for prevent a SQL Injection, using MySQL specific function
	 * or simple addslashes (if MySQL Connection is not attempt). Also remove
	 * \r \n and \x1a symbols.
	 *
	 * @param string $string String to be escaped
	 * @return string Escaping string
	 */
	public function escapeString($string) {
		if ($this->_connection) {
			return mysql_real_escape_string($string);
		}
		else {
			return str_replace(
				array("\n", "\r", "\x1a"),
				array("\\n", "\\r", "\Z"),
				addslashes($string));
		}
	}


	/**
	 * Unescaping strings for passwords or other stuff, that no need of escaping
	 *
	 * @param string $string String to unescape
	 * @param bool $escaped True if previous escaped string, false - otherwise
	 * @return string Unenscape string
	 */
	public function unescapeString($string, $escaped = false)
	{
		if ($escaped) {
			return stripslashes($string);
		} else {
			if (get_magic_quotes_gpc()) $string = stripslashes($string);
		}
	}


	public function fieldInfo($index)
	{
		return array(
			'type' =>
				mysql_field_type($this->_result, intval($index)),
			'length' =>
				mysql_field_len($this->_result, intval($index))
		);
	}

	/**
	 * Get Count of SQL Queries
	 *
	 * @return integer
	 */
	public function getSqlCount()
	{
		return $this->_sqlCount;
	}

	private function _setStatus($status)
	{
		$this->_status = $status;
	}

	private function _setErrorMsg($message)
	{
		$this->_errorMessages[] = $message;
	}

};

################################################################################
# Usage
/*******************************************************************************
	$db_conf = array(
		'HOSTNAME' => "hostname",
		'USERNAME' => "username",
		'PASSWORD' => "password",
		'DATABASE' => "database",
		'CODEPAGE' => "utf8",
		'CONNPORT' => "3306"
	);
	$db = new MYSQL($db_conf, true);

	if ($db->getStatus() !== true) {
		foreach($db->getErrors() as $val) echo $val.'<br />';
		exit();
	}

	$query = "SELECT * FROM `table`";

	if ($db->query($query) !== true) {
		foreach($db->getErrors() as $val) echo $val.'<br />';
		exit();
	}

	$result = array();
	$tmp = array();

	while ($tmp = $db->getRow()) {
		$result[] = $tmp;
	}

	foreach ($result as $key => $value) {
		echo '['.$key.'] = '.$value.";<br />\n";
	}

*******************************************************************************/

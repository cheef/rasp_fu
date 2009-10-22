<?php

	rasp_lib(
		'types.array',
		'resources.abstract_resource',
		'exception', 'tools.catcher'
	);

	class RaspDatabase extends RaspAbstractResource {

		public $handler;
		protected $options, $errors = array();
		protected static $default_options = array('host' => 'localhost', 'charset' => 'utf8', 'password' => '');
		public static $current;

		const EXCEPTION_CONNECTION_ERROR = "There is connection problem, check settings";

		public function __construct($options) {
			$this->options = RaspHash::merge(self::$default_options, $options);

			$this->connect($this->options['host'], $this->options['user'], $this->options['password']);

			if (RaspArray::index($this->options, 'database', false)) $this->select_db($this->options['database']);
		}

		/**
		 * Connect to database
		 * @param String $host
		 * @param String $user
		 * @param String $pass
		 * @return Resource || false
		 */
		protected function connect($host, $user, $pass){
			try {	
				if (false === ($this->handler = mysql_connect($host, $user, $pass)))
					throw new RaspException(self::EXCEPTION_CONNECTION_ERROR);

				return $this->handler;
			} catch (RaspException $e) { RaspCatcher::add($e); }
		}

		protected function select_db($name){
			return mysql_select_db($name, $this->handler);
		}

		/**
		 * Send query to database
		 * @param String $sql
		 * @return Resource || false
		 */
		public function query($sql){
			
			if (defined('RASP_DEBUG_LEVEL') && RASP_DEBUG_LEVEL === 'all') {
				$file = RaspFile::create(array('source' => 'development.log', 'mode' => 'a+'));
				$file->write($sql . "\n");
				$file->close();
			}

			try {
				if (false === ($result = mysql_query($sql, $this->handler))) 
					throw new RaspException('Error occured during query: ' . $sql . "\n" . 'Mysql error[' . $this->error_number() . ']:' . $this->error_message() . "\n");

				return $result;
			} catch (RaspException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Return last insert id
		 * @return String
		 */
		public function last_insert_id() {
			return mysql_insert_id($this->handler);
		}

		public static function create($options){
			return self::$current = new RaspDatabase($options);
		}

		public function fetch($reponse_resource){
			return mysql_fetch_assoc($reponse_resource);
		}

		public function close(){
			$returning = mysql_close($this->handler);
			$this->__destruct();
			return $returning;
		}

		public function drop($table_name){
			return (empty($table_name) ? false : $this->query('DROP TABLE IF EXISTS '. $table_name . ';'));
		}

		public function error_message(){
			return mysql_error($this->handler);
		}

		public function error_number(){
			return mysql_errno($this->handler);
		}

		public function escape($target){
			return mysql_escape_string($target);
		}
	}

?>
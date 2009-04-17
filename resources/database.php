<?php

	require_once RASP_RESOURCES_PATH . 'abstract_resource.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_PATH . 'exception.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';

	class RaspDatabaseConnectionException extends RaspException { public $message = "There is connection problem, check settings"; }
	class RaspDatabaseQueryException extends RaspException {

		public $message = "There is error during query";

		public function __construct($options = array()){
			if(RaspArray::index($options, 'sql', false)) $this->query = $options['sql'];
			if(RaspArray::index($options, 'error_message', false)) $this->error_message = $options['error_message'];
			if(RaspArray::index($options, 'error_number', false)) $this->error_number = $options['error_number'];
			$this->has_additional = true;
		}
	}

	class RaspDatabase extends RaspAbstractResource {

		public static $current;
		protected $handler, $options, $errors = array();
		protected static $default_options = array('host' => 'localhost', 'charset' => 'utf8', 'password' => '');

		public function __construct($options) {
			$this->options = array_merge(self::$default_options, $options);
			$returning = $this->connect($this->options['host'], $this->options['user'], $this->options['password']);
			if(RaspArray::index($this->options, 'database', false)) $this->select_db($this->options['database']);
			return $returning;
		}

		protected function connect($host, $user, $pass){
			try {	if(!($this->handler = mysql_connect($host, $user, $pass))) throw new RaspDatabaseConnectionException; return $this->handler;}
			catch(RaspDatabaseConnectionException $e){ RaspCatcher::add($e); }
		}

		protected function select_db($name){
			return mysql_select_db($name, $this->handler);
		}

		public function query($sql = ""){
			try {
				if(!($result = mysql_query($sql, $this->handler))) throw new RaspDatabaseQueryException(array(
					'sql' => $sql,
					'error_message' => $this->error_message(),
					'error_number' => $this->error_number()
				)); return $result; }
			catch(RaspDatabaseQueryException $e) { RaspCatcher::add($e); }
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
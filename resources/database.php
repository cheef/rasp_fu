<?php

	require_once RASP_RESOURCES_PATH . 'abstract_resource.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspDatabase extends RaspAbstractResource {

		public static $current;
		protected $handler, $options, $errors = array();
		protected static $default_options = array('host' => 'localhost', 'charset' => 'utf8', 'password' => '');

		public function RaspDatabase($options) {
			$this->options = array_merge(self::$default_options, $options);
			$returning = $this->connect($this->options['host'], $this->options['user'], $this->options['password']);
			if(RaspArray::index($this->options, 'database', false)) $this->select_db($this->options['database']);
			return $returning;
		}

		protected function connect($host, $user, $pass){
			return $this->handler = mysql_connect($host, $user, $pass);
		}

		protected function select_db($name){
			return mysql_select_db($name, $this->handler);
		}

		public function query($sql = ""){
			return mysql_query($sql, $this->handler);
		}

		public static function create($options){
			return self::$current = new RaspDatabase($options);
		}

		public function fetch($reponse_resource){
			return mysql_fetch_assoc($reponse_resource);
		}

		public function close(){
			return mysql_close($this->handler);
		}
	}

?>
<?php

	require_once RASP_TYPES_PATH . 'string.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_RESOURCES_PATH . 'database.php';

	class RaspActiveRecord {

		public static $db = null;
		public static $connection_params = array();
		public static $table_name, $class_name = 'RaspActiveRecord';
		public static $database_driver = 'RaspDatabase';
		public $attributes;

		public function __construct($params){
			foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public function set($attribute, $value){
			eval("return \$this->" . RaspString::underscore($attribute) . " = \$value;");
		}

		public function __set($attribute, $value){
			return $this->attributes[$attribute] = $value;
		}

		public function __get($attribute){
			return RaspArray::index($this->attributes, $attribute, null);
		}

		public static function find($mode, $options = array()){
			switch($mode){
				case 'all': return self::find_all($options);
			}
		}

		public static function create($params, $saving = true){
			$object = new $class_name($params);
			return ($saving ? $object->save() : $object);
		}

		public function save(){
		}

		public function update(){
		}

		public function insert(){
		}

		public static function find_all($options = array()){
			return self::find_by_sql('SELECT * FROM ' . self::$table_name);
		}

		public static function find_by_sql($sql){
			self::establish_connection();
			$returning = array();
			$reponse_resource = self::$db->query($sql);
			while($result = self::$db->fetch($reponse_resource)) $returning[] = new self::$class_name($result);
			self::close_connection();
			return $returning;
		}

		public static function establish_connection(){
			if(empty(self::$connection_params)) die('No params was assign to database connect');
			return (self::is_connection_established() ? self::$db : self::$db = new self::$database_driver(self::$connection_params));
		}

		public static function close_connection(){
			return self::$db->close();
		}

		public static function is_connection_established(){
			return !empty(self::$db);
		}
	}
?>
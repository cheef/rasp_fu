<?php

	require_once RASP_TYPES_PATH . 'string.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_TYPES_PATH . 'hash.php';
	require_once RASP_RESOURCES_PATH . 'database.php';
	require_once RASP_PATH . 'exception.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_ORM_PATH . 'active_field.php';

	class RaspDatabaseParamsException extends RaspException { public $message = 'No connection params for database'; }
	class RaspARConnectionException extends RaspException { public $message = 'No connection with database'; }

	class RaspActiveRecord {

		public static $db = null;
		public static $connection_params = array();
		public static $table_name, $class_name = __CLASS__, $table_fields = array(), $fields = array();
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
				case 'first': return self::find_first($options);
			}
		}

		private static function conditions($options){
			return '';
		}

		public static function find_first($options){
			return RaspArray::first(self::find_by_sql('SELECT * FROM ' . self::$table_name . ' ' . self::conditions($options) . ' LIMIT 1'));
		}

		public static function create($params, $saving = true){
			$object = new $class_name($params);
			return ($saving ? $object->save() : $object);
		}

		public static function table_fields(){
			if(empty(self::$table_fields)){
				try {
					if(!self::establish_connection()) throw new RaspARConnectionException;
					$reponse_resource = self::$db->query('SHOW COLUMNS FROM ' . self::$table_name);
					while($result = self::$db->fetch($reponse_resource)) self::$table_fields[] = RaspActiveField::create($result);
					return self::$table_fields;
				} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
			} else return self::$table_fields;
		}

		public static function fields(){
			return (empty(self::$fields) ? RaspHash::map(self::table_fields(), 'field') : self::$fields);
		}

		public function save(){
			return ($this->has_id() ? $this->update() : $this->insert());
		}

		public function update(){
			try {
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$strings_for_update = array();
				foreach($this->attributes as $attribute => $value)
				if(in_array($attribute, $this->only_table_attributes())) $strings_for_update[] = self::escape($attribute, '`') . ' = ' . self::escape($value);
				return self::$db->query('UPDATE ' . self::$table_name . ' SET ' . join(',', $strings_for_update) . ' WHERE `id` = ' . $this->attributes['id']);
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public function insert(){
			try {
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$sql = "INSERT INTO " . self::$table_name . "(" . join(',', self::escape($this->only_table_attributes(), '`')) . ") VALUES (" . join(',', self::escape($this->only_table_values())) . ");";
				return self::$db->query($sql);
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public function only_table_attributes(){
			$fields = array();
			foreach($this->attributes_names() as $name) if(in_array($name, self::fields())) $fields[] = $name;
			return $fields;
		}

		public function only_table_values(){
			$values = array();
			foreach($this->attributes_names() as $name) if(in_array($name, self::fields())) $values[] = $this->attributes[$name];
			return $values;
		}

		public function has_id(){
			return isset($this->attributes['id']) && !empty($this->attributes['id']);
		}

		public function attributes_names(){
			return RaspArray::keys($this->attributes);
		}

		public function values(){
			$values = array();
			foreach($this->attributes as $attribute => $value) $values[] = $value;
			return $values;
		}

		public static function escape($target, $escaper = "'"){
			self::establish_connection();
			if(is_array($target)) foreach($target as $key => $element) $target[$key]  = $escaper . self::$db->escape($element) . $escaper;
			else $target = $escaper . self::$db->escape($target) . $escaper;
			return $target;
		}

		public static function find_all($options = array()){
			return self::find_by_sql('SELECT * FROM ' . self::$table_name . ' ' . self::conditions($options));
		}

		public static function find_by_sql($sql){
			try {
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$returning = array();
				$reponse_resource = self::$db->query($sql);
				while($result = self::$db->fetch($reponse_resource)) $returning[] = new self::$class_name($result);
				return $returning;
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public static function establish_connection(){
			try {
				if(empty(self::$connection_params)) throw new RaspDatabaseParamsException;
				return (self::is_connection_established() ? self::$db : (self::$db = new self::$database_driver(self::$connection_params)));
			} catch(RaspDatabaseParamsException $e) { RaspCatcher::add($e); }
		}

		public static function close_connection(){
			return self::$db->close();
		}

		public static function is_connection_established(){
			return !empty(self::$db);
		}
	}
?>
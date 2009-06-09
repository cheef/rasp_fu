<?php

	require_once RASP_TYPES_PATH . 'string.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_TYPES_PATH . 'hash.php';
	require_once RASP_RESOURCES_PATH . 'database.php';
	require_once RASP_PATH . 'exception.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_ORM_PATH . 'active_field.php';
	require_once RASP_ORM_PATH . 'interfaces/model.php';
	require_once RASP_ORM_PATH . 'sql_constructor.php';
	require_once RASP_ORM_PATH . 'constructions/expression.php';

	class RaspDatabaseParamsException extends RaspException { public $message = 'No connection params for database'; }
	class RaspARConnectionException extends RaspException { public $message = 'No connection with database'; }
	class RaspActiveRecordException extends RaspException {};

	class RaspActiveRecord implements RaspModel {

		public static $db = null;
		public static $children = null;
		public static $connection_params = array();
		public static $table_name, $class_name = __CLASS__, $table_fields = array(), $fields = array();
		public static $database_driver = 'RaspDatabase';
		public static $underscored = true;
    public static $options = array('underscored' => true);
    public static $per_page = 10;
		public $attributes;

		const EXCEPTION_WRONG_FIND_MODE = "Wrong find mode, try others, like 'all' or 'first'";
		const EXCEPTION_MISSED_ID = "Missed id param";

		public function __construct($params = array()){
			if(!empty($params)) foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public function __set($attribute, $value){
			return $this->attributes[$attribute] = $value;
		}

		public function __get($attribute){
			return RaspArray::index($this->attributes, $attribute, null);
		}

		public function set($attribute, $value){
			eval("return \$this->" . (self::$underscored ? RaspString::underscore($attribute) : $attribute) . " = \$value;");
		}

		#Find methods

		public static function find($mode, $options = array()){
			try {
				if(is_int(intval($mode)) && (intval($mode) != 0)) return self::find_by_id($mode, $options);
				switch($mode){
					case 'all': return self::find_all($options);
					case 'first': return self::find_first($options);
					case 'count': return self::find_count($options);
					case 'constructor': return self::find_by_constructor();
					default: throw new RaspActiveRecordException(self::EXCEPTION_WRONG_FIND_MODE); break;
				}
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		public static function find_by_id($id, $options = array()){
			try {
				if(empty($id)) throw new RaspActiveRecordException(self::EXCEPTION_MISSED_ID);
				$q = self::find('constructor');
				$q->select('all')
					->from(self::$table_name)
					->where(self::conditions($options))
					->where(array('id' => $id))
					->order(self::order_by($options))
					->limit(self::limit($options))
					->offset(self::offset($options));
				return RaspArray::first(self::find_by_sql($q->to_sql()));
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		public static function find_count($options = array()){
			try {
				$q = self::find('constructor');
				$q->select('COUNT(*)')
				->from(self::$table_name)
				->where(self::conditions($options))
				->limit(1);
				if(!self::establish_connection()) throw new RaspARConnectionException;
				return RaspArray::first(self::$db->fetch(self::$db->query($q->to_sql())));
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public static function find_first($options = array()){
			$q = self::find('constructor');
			$q->select('all')
				->from(self::$table_name)
				->where(self::conditions($options))
				->limit(1);
			return self::find_by_sql($q->to_sql());
		}

		public static function find_all($options = array()){
			$q = self::find('constructor');
			$q->select('all')
				->from(self::$table_name)
				->where(self::conditions($options))
				->order(self::order_by($options))
				->limit(self::limit($options))
				->offset(self::offset($options));
			return self::find_by_sql($q->to_sql());
		}

		public static function find_by_sql($sql){
			try {
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$returning = array();
				$reponse_resource = self::$db->query($sql);
				eval('while($result = self::$db->fetch($reponse_resource)) $returning[] = new ' . self::$class_name . '($result);');
				return $returning;
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public static function find_by_constructor(){
			return RaspSQLConstructor::create('select');
		}

    #Paginator

    public static function paginate($page_num = 1, $options = array()){
      $options = array_merge($options, array('limit' => self::$per_page, 'offset' => (($page_num - 1) * self::$per_page)));
      return self::find('all', $options);
    }

    public static function pages($options = array()){
      $records = self::find('count', $options);
      return ceil($records/self::$per_page);
    }

		#SQL constructor

		public function q(){
			return RaspWhereExpression::create();
		}

		private static function conditions($options = array()){
			return (!empty($options) && ($conditions = RaspArray::index($options, 'conditions', false)) ? $conditions : null);
		}

		private static function limit($options = array()){
			return (!empty($options) && ($limit = RaspArray::index($options, 'limit', false)) ? $limit : null);
		}

		private static function order_by($options = array()){
			return (!empty($options) && ($order_by = RaspArray::index($options, 'order', false)) ? $order_by : null);
		}

		private static function offset($options = array()){
			return (!empty($options) && ($offset = RaspArray::index($options, 'offset', false)) ? $offset : null);
		}

		#CRUD

		public static function create($params, $saving = true){
			$object = new self::$class_name($params);
			return ($saving ? $object->save() : $object);
		}

		public function save($attributes = array(), $validate = true){
      if(!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);
      if($validate && $this->is_valid())
        return ($this->has_id() ? $this->update($attributes) : $this->insert($attributes));
      elseif(!$validate) return ($this->has_id() ? $this->update($attributes) : $this->insert($attributes));
      else return false;
		}

		public function update($attributes = array()){
			try {
				self::initilize();
				if(!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$strings_for_update = array();
				foreach($this->attributes as $attribute => $value)
					if(in_array($attribute, $this->only_table_attributes())) $strings_for_update[] = self::escape($attribute, '`') . ' = ' . self::escape($value);
				return self::$db->query('UPDATE ' . self::$table_name . ' SET ' . join(',', $strings_for_update) . ' WHERE `id` = ' . $this->attributes['id']);
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

		public function insert($attributes = array()){
			try {
				self::initilize();
				if(!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);
				if(!self::establish_connection()) throw new RaspARConnectionException;
				$sql = "INSERT INTO " . self::$table_name . "(" . join(',', self::escape($this->only_table_attributes(), '`')) . ") VALUES (" . join(',', self::escape($this->only_table_values())) . ");";
				return self::$db->query($sql);
			} catch(RaspARConnectionException $e){ RaspCatcher::add($e); }
		}

    public function delete(){
      return self::$db->query("DELETE FROM " . self::$table_name . " WHERE `id` = " . $this->id);
    }

		public function update_all($attributes){
			foreach($attributes as $attribute => $value) $this->set($attribute, $value);
			return $this->update();
		}

		#Connection methods

		public static function initilize(){
		}

		public static function options(){

		}

		public static function establish_connection($forcing = false){
			try {
				eval('$connection_params = ' . self::$class_name .'::$connection_params;');
				if(empty($connection_params)) throw new RaspDatabaseParamsException;
				eval('$db = (' . self::$class_name . '::is_connection_established() && !$forcing) ? ' . self::$class_name . '::$db : new self::$database_driver($connection_params);');
				eval(self::$class_name . '::$db = $db;');
				return $db;
			} catch(RaspDatabaseParamsException $e) { RaspCatcher::add($e); }
		}

		public static function close_connection(){
			eval('$closing = ' . self::$class_name . '::$db->close();');
			return $closing;
		}

		public static function is_connection_established(){
			eval('$established = !empty(' . self::$class_name . '::$db);');
			return $established;
		}

		#Validation

		public function is_valid(){
      return true;
		}

		#Other methods

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

    public function is_new_record(){
      return !$this->has_id();
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
	}
?>
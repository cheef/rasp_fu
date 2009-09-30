<?php

	/**
	 * This class provides ORM functionality
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 * @link http://wiki.github.com/cheef/rasp_fu/rasp-orm
	 * $Id: active_record.php,v 1.2 2009/09/30 11:15:03 igarmatenko Exp $
	 */

	rasp_lib(
		'types.string', 'types.array', 'types.hash',
		'resources.database',
		'exception', 'tools.catcher',
		'orm.interfaces.model', 'orm.active_field', 'orm.sql_constructor', 'orm.constructions.expression',
		'validation.validator_manager'
	);

	class RaspActiveRecordException extends RaspException {};

	class RaspActiveRecord implements RaspModel {

		protected static $connections = array();
		protected static $class_name = __CLASS__;
		public static $connection_params = array();
		public static $table_name, $table_fields = array(), $fields = array();
		public static $options = array(
			'underscored' => true,
			'id_field' => 'id',
			'database' => array(
				'driver' => 'RaspDatabase'
			),
			'pagination' => array(
				'per_page' => 10
			)
		);

		public $attributes;
		protected static $validate = array();
		protected $errors;

		const EXCEPTION_WRONG_FIND_MODE        = "Wrong find mode, try others, like 'all' or 'first'";
		const EXCEPTION_MISSED_ID              = "Missed id param";
		const EXCEPTION_MISSED_DATABASE_PARAMS = "Missed database connection params";
		const EXCEPTION_NO_CONNECTION_WITH_DB  = "Error, no connection with database";

		public function __construct($params = array()){
			if (!empty($params)) foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		# Settets and getters

		public function __set($attribute, $value){
			return $this->attributes[$attribute] = $value;
		}

		public function __get($attribute){
			return RaspHash::get($this->attributes, $attribute, null);
		}

		public function set($attribute, $value){
			eval('return $this->' . (self::options('underscored') ? RaspString::underscore($attribute) : $attribute) . ' = $value;');
		}

		#Find methods

		/**
		 * Find records from database, constructs query and parse it into objects
		 * @param String || Integer || Array $mode
		 * @param Hash $options
		 * @return Boolean || Object || Array
		 */
		public static function find($mode, $options = array()){
			try {
				
				switch($mode){
					case 'all':         return self::find_all($options);
					case 'first':       return self::find_first($options);
					case 'count':       return self::find_count($options);
					case 'constructor': return self::find_by_constructor();
					default:
						if (is_numeric($mode)) return self::find_by_id($mode, $options);
						if (is_array($mode))   return self::find_by_ids($mode, $options);
						throw new RaspActiveRecordException(self::EXCEPTION_WRONG_FIND_MODE);
						break;
				}
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		public static function find_by_sql($sql, $options = array()){
			try {
				self::class_name($options);

				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				$returning = array();
				$reponse_resource = self::$connections[self::class_name()]->query($sql);
				eval('while($result = self::$connections[self::class_name()]->fetch($reponse_resource)) $returning[] = new ' . self::class_name() . '($result);');
				return $returning;
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}

		/**
		 * Find record by id
		 * @param Integer || String $id
		 * @param Hash $options
		 * @return Boolean
		 */
		protected static function find_by_id($id, $options = array()){
			try {
				if (empty($id) || $id === 0 || $id === '0') throw new RaspActiveRecordException(self::EXCEPTION_MISSED_ID);

				self::class_name($options);

				$q = self::find('constructor');
				$q->select(RaspHash::get($options, 'fields', 'all'))
					->from(self::table_name() )
					->where(self::conditions($options))
					->where(array('id' => (int) $id))
					->order(self::order_by($options))
					->limit(self::limit($options))
					->offset(self::offset($options));

				return RaspHash::first(self::find_by_sql($q->to_sql()));
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		protected static function find_by_ids($ids, $options = array()) {
			return false;
		}

		protected static function find_count($options = array()){
			try {
				self::class_name($options);

				$q = self::find('constructor');
				$q->select('COUNT(*)')
				->from(self::table_name())
				->where(self::conditions($options))
				->limit(1);

				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				return RaspArray::first(self::$connections[self::class_name()]->fetch(self::$connections[self::class_name()]->query($q->to_sql())));
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}

		protected static function find_first($options = array()){
			self::class_name($options);

			$q = self::find('constructor');
			$q->select(RaspHash::get($options, 'fields', 'all'))
				->from(self::table_name())
				->where(self::conditions($options))
				->limit(1);

			return RaspHash::first(self::find_by_sql($q->to_sql()));
		}

		protected static function find_all($options = array()){
			self::class_name($options);

			$q = self::find('constructor');
			$q->select(RaspHash::get($options, 'fields', 'all'))
				->from(self::table_name())
				->where(self::conditions($options))
				->order(self::order_by($options))
				->limit(self::limit($options))
				->offset(self::offset($options));
			return self::find_by_sql($q->to_sql());
		}

		protected static function find_by_constructor($options = array()){
			self::class_name($options);
			return RaspSQLConstructor::create('select');
		}

		#Paginator

		public static function paginate($page_num = 1, $options = array()){
			self::class_name($options);
			$options = array_merge($options, array('limit' => self::$options['pagination']['per_page'], 'offset' => (($page_num - 1) * self::$options['pagination']['per_page'])));
			return self::find('all', $options);
		}

		public static function pages($options = array()){
			self::class_name($options);
			$records = self::find('count', $options);
			return ceil($records/self::$options['pagination']['per_page']);
		}

		#SQL constructor

		public static function q(){
			return RaspWhereExpression::create();
		}

		protected static function conditions($options = array()){
			return (!empty($options) && ($conditions = RaspHash::get($options, 'conditions', false)) ? $conditions : null);
		}

		protected static function limit($options = array()){
			return (!empty($options) && ($limit = RaspHash::get($options, 'limit', false)) ? $limit : null);
		}

		protected static function order_by($options = array()){
			return (!empty($options) && ($order_by = RaspHash::get($options, 'order', false)) ? $order_by : null);
		}

		protected static function offset($options = array()){
			return (!empty($options) && ($offset = RaspHash::get($options, 'offset', false)) ? $offset : null);
		}

		#CRUD

		public static function create($params, $options = array()){
			$saving = isset($options['save']) ? $options['save'] : true;
			self::class_name($options);
			eval('$object = new ' . self::class_name() . '($params);');
			return ($saving ? $object->save() : $object);
		}

		public static function initialize($params, $options = array()){
			eval('$object = new ' . self::class_name($options) . '($params);');
			return $object;
		}

		public function save($attributes = array(), $validate = true){
			if(!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);

			if($validate){
				if($this->is_valid()) return ($this->is_new_record() ? $this->insert() : $this->update());
				else return false;
			}
			else return ($this->is_new_record() ? $this->insert() : $this->update());
		}

		protected function update($attributes = array()){
			try {
				if(!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);
				if(!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				$strings_for_update = array();
				foreach($this->attributes as $attribute => $value)
					if(in_array($attribute, $this->only_table_attributes())) $strings_for_update[] = self::escape($attribute, '`') . ' = ' . self::escape($value);
				$query = 'UPDATE ' . self::table_name() . ' SET ' . join(',', $strings_for_update) . ' WHERE `' . self::options('id_field') . '` = ' . $this->attributes(self::options('id_field'));
				$saved = self::$connections[self::class_name()]->query($query);
				return ($saved ? $this : false);
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}

		protected function insert($attributes = array()){
			try {
				if (!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);
				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);

				$sql = "INSERT INTO " . self::table_name() . "(" . join(',', self::escape($this->only_table_attributes(), '`')) . ") VALUES (" . join(',', self::escape($this->only_table_values())) . ");";
				return self::$connections[self::class_name()]->query($sql);
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}

		public function delete(){
		return self::$connections[self::class_name()]->query("DELETE FROM " . self::table_name() . " WHERE `" . self::options('id_field') . "` = " . $this->id);
	}

		public function update_all($attributes){
			foreach($attributes as $attribute => $value) $this->set($attribute, $value);
			return $this->update();
		}

		#Connection methods

		public static function class_name($options = null) {
			return (empty($options) ? self::$class_name : self::$class_name = RaspHash::get($options, 'class', __CLASS__));
		}

		public static function establish_connection($forcing = false){
			try {
				eval('$connection_params = ' . self::class_name() .'::$connection_params;');
				if(empty($connection_params)) $connection_params = self::$connection_params;
				if(empty($connection_params)) throw new RaspActiveRecordException(self::EXCEPTION_MISSED_DATABASE_PARAMS);
				$connection = (self::is_connection_established() && !$forcing ? self::$connections[self::class_name()] : self::$connections[self::class_name()] = new self::$options['database']['driver']($connection_params));
				return $connection;
			} catch(RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		public static function close_connection(){
			$returning = self::$connections[self::class_name()]->close();
			if($returning) self::$connections[self::class_name()] = null;
			return $returning;
		}

		public static function close_all_connections(){
			foreach(self::$connections as $connection) $connection->close();
			self::$connections = null;
			return true;
		}

		public static function is_connection_established(){
			$connection = RaspHash::get(self::$connections, self::class_name(), null);
			return !empty($connection);
		}

		public static function connection($class_name){
			return self::$connections[$class_name];
		}

		#Attributes

		public function attributes($attribute_name = null){
			if(empty($attribute_name)) return $this->attributes;
			return RaspHash::get($this->attributes, $attribute_name, null);
		}

		#Validation

		public function is_valid(){
			return $this->validate() && !$this->has_errors();
		}

		public function validate(){
			eval('$validate = ' . get_class($this) . '::$validate;');
			foreach($validate as $attribute_name => $validate_options) $this->validate_attribute($attribute_name, $validate_options);
			return true;
		}

		public function has_errors(){
			return !empty($this->errors);
		}

		public function errors($attribute_name = null, $message = null){
			return empty($attribute_name) ? $this->errors : $this->errors[$attribute_name][] = $message;
		}

		protected function validate_attribute($attribute_name, $validate_options){
			$validate_options = array_merge($validate_options, array('object' => $this));
			$validator_manager = RaspValidatorManager::initilize($validate_options);
			if(!$validator_manager->is_valid($this->attributes($attribute_name))) $this->errors[$attribute_name] = $validator_manager->messages();
		}

		#Other methods

		public static function table_name(){
			eval('$table_name = ' . self::class_name() . '::$table_name;');
			if (empty($table_name)) $table_name = RaspString::tableize(self::$class_name);
			return $table_name;
		}

		public static function options($option_name){
			eval('$class_options = ' . self::class_name() . '::$options;');
			$options = array_merge(self::$options, $class_options);
			return $options[$option_name];
		}

		public static function table_fields(){
			try {
				if(RaspArray::is_empty(self::$table_fields, self::class_name())){
					if(!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
					$reponse_resource = self::$connections[self::class_name()]->query('SHOW COLUMNS FROM ' . self::table_name());
					while($result = self::$connections[self::class_name()]->fetch($reponse_resource)) {
						$result = array_merge($result, array('underscored' => self::options('underscored')));
						self::$table_fields[self::class_name()][] = RaspActiveField::create($result);
					}
					return self::$table_fields[self::class_name()];
				} else return self::$table_fields[self::class_name()];
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}

		public static function fields(){
			return (RaspArray::is_not_empty(self::$table_fields, self::class_name()) ?
				RaspHash::map(self::$table_fields[self::class_name()], 'field') : RaspHash::map(self::table_fields(), 'field'));
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
			$id = $this->attributes(self::options('id_field'));
			return !empty($id);
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
			try {
				if(!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				if(is_array($target)) foreach($target as $key => $element) $target[$key]  = $escaper . self::$connections[self::class_name()]->escape($element) . $escaper;
				else $target = $escaper . self::$connections[self::class_name()]->escape($target) . $escaper;
				return $target;
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}
	}
?>
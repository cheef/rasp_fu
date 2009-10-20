<?php

	/**
	 * This class provides ORM functionality
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 * @link http://wiki.github.com/cheef/rasp_fu/rasp-orm
	 */

	rasp_lib(
		'types.string', 'types.array', 'types.hash',
		'resources.database',
		'exception', 'tools.catcher',
		'orm.interfaces.model', 'orm.active_field', 'orm.sql_constructor', 'orm.constructions.expression', 'orm.active_record_collection',
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

		public $attributes = array();
		protected static $validate = array();
		protected $errors;

		const EXCEPTION_WRONG_FIND_MODE        = "Wrong find mode, try others, like 'all' or 'first'";
		const EXCEPTION_MISSED_ID              = "Missed id param";
		const EXCEPTION_MISSED_DATABASE_PARAMS = "Missed database connection params";
		const EXCEPTION_NO_CONNECTION_WITH_DB  = "Error, no connection with database";
		const EXCEPTION_NO_SQL_TO_EXECUTE      = "Error, no sql assigned to execute";
		const EXCEPTION_NOT_ENOUGH_ARGUMENTS   = "Error, not enough arguments for method";

		public function __construct($params = array()){
			if (!empty($params)) {
				foreach($params as $attribute_name => $value) $this->set($attribute_name, $value);
			}
		}

		# Attributes settets and getters

		public function __set($attribute_name, $value){
			return $this->set($attribute_name, $value);
		}

		public function __get($attribute_name){
			return $this->get($attribute_name);
		}

		/**
		 * Attribute setter
		 * @param String $attribute_name
		 * @param Any $value
		 * @return Any
		 */
		public function set($attribute_name, $value){
			$attribute_name = self::options('underscored') ? RaspString::underscore($attribute_name) : $attribute_name;
			return $this->attributes[$attribute_name] = $value;
		}

		/**
		 * Attribute getter
		 * @param String $attribute_name
		 * @return Any
		 */
		public function get($attribute_name) {
			if (in_array($attribute_name, array_keys($this->attributes))) {
				return $this->attributes[$attribute_name];
			}
			return null;
		}

		/**
		 * Unified getter-setter method to attribute access
		 * @return Any
		 */
		public function attribute() {
			$arguments = func_get_args();
			if (!isset($arguments[0])) throw RaspActiveRecordException(self::EXCEPTION_NOT_ENOUGH_ARGUMENTS);

			# Call setter if value assigned
			if (isset($arguments[1])) return $this->set($arguments[0], $arguments[1]);
			# Else call getter
			return $this->get($arguments[0]);
		}

		/**
		 * Mass attributes assigner
		 * @param  Hash $attributes
		 * @return Array
		 */
		public function attributes($attributes = array()){
			if (empty($attributes)) return $this->attributes;

			foreach ($attributes as $attribute_name => $value) {
				$this->set($attribute_name, $value);
			}

			return $this->attributes;
		}

		#Find methods

		/**
		 * Find records from database, constructs query and parse it into objects
		 * @param String || Integer || Array $mode
		 * @param Hash $options
		 * @return false || Object || Array
		 */
		public static function find($mode, $options = array()){
			try {

				# Casing work modes
				switch ($mode){
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

		/**
		 * Found records by custom sql
		 * @param String $sql
		 * @param Hash $options
		 * @return false || Array
		 */
		public static function find_by_sql($sql, $options = array()){
			try {

				if (empty($sql)) throw new RaspActiveRecordException(self::EXCEPTION_NO_SQL_TO_EXECUTE);

				$class_name = self::class_name($options);
				$connection = self::establish_connection($class_name);
				$request    = $connection->query($sql);

				if (false === self::need_fetch($options)) {
					return RaspActiveRecordCollection::initialize(array(
						'connection'   => $connection,
						'request'      => $request,
						'object_class' => $class_name,
					));
				}

				$returning = array();
				while ($result = $connection->fetch($request)) {
					$returning[] = new $class_name($result);
				}

				return $returning;

			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Find record by id
		 * @param Integer || String $id
		 * @param Hash $options
		 * @return Object
		 */
		protected static function find_by_id($id, $options = array()){
			try {
				if (empty($id) || $id === 0 || $id === '0') throw new RaspActiveRecordException(self::EXCEPTION_MISSED_ID);

				$q = self::find('constructor');
				$q->select(RaspHash::get($options, 'fields', 'all'))
				  ->from(self::table_name($options))
				  ->where(self::conditions($options))
				  ->where(array('id' => (int) $id))
				  ->order(self::order_by($options))
				  ->limit(self::limit($options))
				  ->offset(self::offset($options));

				return RaspHash::first(self::find_by_sql($q->to_sql(), $options));
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Find records by ids
		 * @param Array $ids
		 * @param Hash $options
		 * @return Array
		 */
		protected static function find_by_ids($ids, $options = array()) {
			return array();
		}

		/**
		 * Count helper
		 * @param Hash $options
		 * @return Integer
		 */
		protected static function find_count($options = array()){
			try {

				$class_name = self::class_name($options);
				$connection = self::establish_connection($class_name);
				$q          = self::find('constructor');

				$q->select('COUNT(*)')
				  ->from(self::table_name($options))
				  ->where(self::conditions($options))
				  ->limit(1);
				$size = RaspArray::first($connection->fetch($connection->query($q->to_sql())));
				
				return (int) $size;

			} catch(RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Find first record
		 * @param Hash $options
		 * @return Object || Array
		 */
		protected static function find_first($options = array()){

			$q = self::find('constructor');
			$q->select(RaspHash::get($options, 'fields', 'all'))
				->from(self::table_name($options))
				->where(self::conditions($options))
				->limit(1);

			return RaspHash::first(self::find_by_sql($q->to_sql(), $options));
		}

		/**
		 * Find all records
		 * @param Hash $options
		 * @return Array
		 */
		protected static function find_all($options = array()){

			$q = self::find('constructor');
			$q->select(RaspHash::get($options, 'fields', 'all'))
				->from(self::table_name($options))
				->where(self::conditions($options))
				->order(self::order_by($options))
				->group(self::group($options))
				->having(self::having($options))
				->limit(self::limit($options))
				->offset(self::offset($options));
			return self::find_by_sql($q->to_sql(), $options);
		}

		/**
		 * Initialize sql constructor
		 * @param Hash $options
		 * @return RaspSQLConstructor
		 */
		protected static function find_by_constructor($options = array()){
			self::class_name($options);
			return RaspSQLConstructor::create('select');
		}

		#Paginator

		public static function paginate($page_num = 1, $options = array()){
			self::class_name($options);
			$options = array_merge($options, array(
				'limit' => self::$options['pagination']['per_page'],
				'offset' => (($page_num - 1) * self::$options['pagination']['per_page'])
			));
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
		
		protected static function having($options = array()){
			return (!empty($options) && ($having = RaspHash::get($options, 'having', false)) ? $having : null);
		}

		protected static function group($options = array()){
			return (!empty($options) && ($group = RaspHash::get($options, 'group', false)) ? $group : null);
		}

		#CRUD

		public static function create($params, $options = array()){
			$saving = isset($options['save']) ? $options['save'] : true;
			self::class_name($options);
			eval('$object = new ' . self::class_name() . '($params);');
			return ($saving ? $object->save() : $object);
		}

		public static function initialize($params = array(), $options = array()){
			$class_name = self::class_name($options);
			return new $class_name($params);
		}

		public function save($attributes = array(), $validate = true){
			if (!empty($attributes)) $this->attributes($attributes);

			if ($validate){
				if ($this->is_valid()) return ($this->is_new_record() ? $this->insert() : $this->update());
				else return false;
			}
			else return ($this->is_new_record() ? $this->insert() : $this->update());
		}

		/**
		 * Update all attributes
		 * @TODO check each attribute change or not
		 * @param Hash $attributes
		 * @param Hash $options
		 * @return Object || false
		 */
		public function update($attributes = array(), $options = array()){
			try {
				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				if (!empty($attributes)) $this->attributes($attributes);

				return $this->update_attributes($this->attributes(), $options);
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Insert into db new record
		 * @param Hash $attributes
		 * @return Object || false
		 */
		protected function insert($attributes = array()){
			try {
				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				if (!empty($attributes)) foreach($attributes as $attribute => $value) $this->set($attribute, $value);				

				$sql = "INSERT INTO " . self::table_name() . "(" . join(',', self::escape($this->only_table_attributes(), '`')) . ") VALUES (" . join(',', self::escape($this->only_table_values())) . ");";
				return self::$connections[self::class_name()]->query($sql);
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		public function delete($options = array()) {
			$sql = "DELETE FROM " . self::table_name($options) . " WHERE `" . self::options('id_field') . "` = " . $this->attributes('id');
			return self::$connections[self::class_name($options)]->query($sql);
		}

		/**
		 * Update only assigned attributes
		 * @TODO construct sql via object constructor as select
		 * @param Hash $attributes
		 * @param Hash $options
		 * @return Object || false
		 */
		public function update_attributes($attributes, $options = array()) {
			try {
				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);

				$strings_for_update = array();
				foreach($attributes as $attribute => $value) {
					if (in_array($attribute, $this->only_table_attributes())) {
						$strings_for_update[] = self::escape($attribute, '`') . ' = ' . self::escape($value);
					}
				}
				$query = 'UPDATE ' . self::table_name($options) . ' SET ' . join(',', $strings_for_update) . ' WHERE `' . self::options('id_field') . '` = ' . $this->attribute(self::options('id_field'));
				$saved = self::$connections[self::class_name($options)]->query($query);
				return ($saved ? $this : false);
			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Delete all records by ids
		 * @TODO  construct sql via object constructor as select
		 * @param Array $ids
		 * @param Hash $options
		 * @return Boolean
		 */
		public static function delete_all($ids, $options = array()) {
			$connection = self::$connections[self::class_name($options)];
			$sql = "DELETE FROM " . self::table_name($options) . " WHERE `" . self::options('id_field') . "` IN (" . join(',', $ids) . ")";
			return $connection->query($sql);
		}

		#Connection methods

		/**
		 * Checks if need fetch all found records, by default is true
		 * @param Hash $options
		 * @return Boolean
		 */
		private static function need_fetch($options) {
			$need_fetch = RaspHash::get($options, 'fetch', true);
			return $need_fetch !== false;
		}

		public static function class_name($options = array()) {
			return (empty($options) ? self::$class_name : self::$class_name = RaspHash::get($options, 'class', __CLASS__));
		}

		/**
		 * Establish connection
		 * @param String $connection_name
		 * @param Boolean $forcing
		 * @return Object
		 */
		public static function establish_connection($connection_name = null, $forcing = false){
			try {
				if (empty($connection_name)) $connection_name = self::class_name();

				if (false === self::is_connection_established($connection_name) || $forcing) {
					# Try get connection params from child class, then from RaspActiveRecord class
					eval('$connection_params = ' . $connection_name .'::$connection_params;');
					if (empty($connection_params)) $connection_params = self::$connection_params;
					if (empty($connection_params)) throw new RaspActiveRecordException(self::EXCEPTION_MISSED_DATABASE_PARAMS);

					# Establish connection
					return self::$connections[$connection_name] = new self::$options['database']['driver']($connection_params);
				}			

				return self::connection($connection_name);

			} catch (RaspActiveRecordException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Close connection, if connection name is not assigned used current connection name
		 * @param String $connection_name
		 * @return Boolean
		 */
		public static function close_connection($connection_name = null){
			$connection = self::connection(empty($connection_name) ? self::class_name() : $connection_name);

			if (!empty($connection) && $connection->close()) {
				self::$connection[self::class_name()] = null;
				return true;
			}

			return false;
		}

		public static function close_all_connections(){
			foreach (self::$connections as $connection) $connection->close();
			self::$connections = null;
			return true;
		}

		/**
		 * Check if connection established, if connection name is not assigned used current connection name
		 * @param String $connection_name
		 * @return Boolean
		 */
		public static function is_connection_established($connection_name = null){
			$connection = self::connection(empty($connection_name) ? self::class_name() : $connection_name);
			return !empty($connection);
		}

		/**
		 * Get database connection object by class name
		 * @param String $class_name
		 * @return Object || null
		 */
		public static function connection($class_name){
			return RaspHash::get(self::$connections, $class_name, null);
		}

		#Validation

		public function is_valid(){
			return $this->validate() && !$this->has_errors();
		}

		public function validate(){
			eval('$validate = ' . get_class($this) . '::$validate;');
			foreach ($validate as $attribute_name => $validate_options) $this->validate_attribute($attribute_name, $validate_options);
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
			if(!$validator_manager->is_valid($this->attribute($attribute_name))) $this->errors[$attribute_name] = $validator_manager->messages();
		}

		#Other methods

		/**
		 * Get table name
		 * @return String
		 */
		public static function table_name($options = array()){
			
			# Get from local options
			$table_name = RaspHash::get($options, 'table');
			$class_name = self::class_name($options);

			# Try get from child class property
			if (empty($table_name)) {
				eval("\$table_name = $class_name::\$table_name;");
			}

			# Get from class name
			return empty($table_name) ? RaspString::tableize($class_name) : $table_name;
		}

		public static function options($option_name){
			eval('$class_options = ' . self::class_name() . '::$options;');
			$options = array_merge(self::$options, $class_options);
			return $options[$option_name];
		}

		public static function table_fields(){
			try {
				if (RaspArray::is_empty(self::$table_fields, self::class_name())){
					if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
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
			$id = $this->attribute(self::options('id_field'));
			return !empty($id);
		}

		public function attributes_names(){
			return RaspArray::keys($this->attributes());
		}

		public function values(){
			$values = array();
			foreach($this->attributes as $attribute => $value) $values[] = $value;
			return $values;
		}

		public static function escape($target, $escaper = "'"){
			try {
				if (!self::establish_connection()) throw new RaspActiveRecordException(self::EXCEPTION_NO_CONNECTION_WITH_DB);
				if (is_array($target)) foreach($target as $key => $element) $target[$key]  = $escaper . self::$connections[self::class_name()]->escape($element) . $escaper;
				else $target = $escaper . self::$connections[self::class_name()]->escape($target) . $escaper;
				return $target;
			} catch(RaspActiveRecordException $e){ RaspCatcher::add($e); }
		}
	}
?>
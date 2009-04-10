<?php

	require_once RASP_PATH . 'abstract_model.php';
	require_once RASP_TYPES_PATH . 'string.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspItem extends RaspAbstractModel {

		public static $table_name = 'items';
		public $attributes = array();
		public $fields = array(
			'formatted_price' => 'formated_amount',
			'total_new' => 'new_count',
			'total_used' => 'used_count',
			'total_collectible' => 'collectible_count',
			'total_refurbished' => 'refurbished_count'
		);

		public function RaspItem($params = array()){
			foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public function set($attribute, $value){
			$this->attributes[RaspString::underscore($attribute)] = $value;
			eval("return \$this->" . RaspString::underscore($attribute) . " = \$value;");
		}

		public function save($db_object){
			if(isset($this->attributes['id']) && !empty($this->attributes['id'])) return $this->update($db_object);
			else return $this->insert($db_object);
		}

		public function insert($db_object){
			return $db_object->query('INSERT INTO ' . self::$table_name . '(' . join(',', self::escape($this->attributes(), $db_object, '`')) . ') VALUES (' . join(',', self::escape($this->values(), $db_object)) . ');');
		}

		public function update($db_object){
			$strings_for_update = array();
			foreach($this->attributes as $field => $value)
				$strings_for_update[] = self::escape($field, $db_object, '`') . ' = ' . self::escape($value, $db_object);
			return $db_object->query('UPDATE ' . self::$table_name . ' SET ' . join(',', $strings_for_update) . ' WHERE `id` = ' . $this->attributes['id']);
		}

		public function merge($params){
			foreach($params as $attribute => $value)
				$this->set((in_array(RaspString::underscore($attribute), RaspArray::keys($this->fields)) ? $this->fields[RaspString::underscore($attribute)] : $attribute), $value);
		}

		public function find_all($db_object, $conditions = null){
			return $db_object->find('all', array('table_name' => self::$table_name, 'conditions' => $conditions));
		}

		public static function escape($target, $db_object, $escaper = "'"){
			if(is_array($target))
				foreach($target as $key => $element) $target[$key]  = $escaper . $db_object->escape($element) . $escaper;
			else $target = $escaper . $db_object->escape($target) . $escaper;
			return $target;
		}

		public function attributes(){
			$attributes = array();
			foreach($this->attributes as $attribute => $value) $attributes[] = in_array($attribute, RaspArray::keys($this->fields)) ? $this->fields[$attribute] : $attribute;
			return $attributes;
		}

		public function values(){
			$values = array();
			foreach($this->attributes as $attribute => $value) $values[] = $value;
			return $values;
		}
	}

	class RaspItemsCollection {
		public static $current = null;

		public $items = array(), $last = null;

		public function RaspItemsCollection(){
			self::$current = $this;
		}

		public static function create(){
			return new RaspItemsCollection;
		}

		public function add($item){
			$this->items[] = $item;
			$this->last = &$this->items[count($this->items) - 1];
			return true;
		}

		public function save_all($db_object){
			$returning = true;
			foreach($this->items as $item) $returning = $returning && $item->save($db_object);
			return $returning;
		}

		public function size(){
			return count($this->items);
		}
	}
?>
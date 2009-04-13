<?php

	require_once RASP_TYPES_PATH . 'string.php';

	class RaspActiveRecord {

		static public $db = null;
		static protected $database_driver = 'RaspDatabase';
		public $attributes, $table_name;

		public function RaspActiveRecord($params){
			foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public function set($attribute, $value){
			$this->attributes[RaspString::underscore($attribute)] = $value;
			eval("return \$this->" . RaspString::underscore($attribute) . " = \$value;");
		}
	}
?>
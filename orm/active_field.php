<?php
	require_once RASP_PATH . 'abstract_object.php';
	require_once RASP_TYPES_PATH . 'string.php';

	class RaspActiveField extends RaspAbstractObject {

		public function __construct($params){
			foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public static function create($options = array()){
			return new RaspActiveField($options);
		}

		public function set($attribute, $value){
			eval("return \$this->" . RaspString::underscore($attribute) . " = \$value;");
		}
	}
?>

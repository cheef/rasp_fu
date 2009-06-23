<?php

  rasp_lib('abstract_object', 'types.string', 'types.array');

	class RaspActiveField extends RaspAbstractObject {

		public $underscored = true;

		public function __construct($params){
			$this->underscored = RaspArray::index($params, 'underscored', true);
			RaspArray::delete($params, 'underscored');
			foreach($params as $attribute => $value) $this->set($attribute, $value);
		}

		public static function create($options = array()){
			return new RaspActiveField($options);
		}

		public function set($attribute, $value){
			eval("return \$this->" . ($this->underscored ? RaspString::underscore($attribute) : $attribute) . " = \$value;");
		}
	}
?>

<?php

  rasp_lib(
    'types.abstract_type',
    'exception', 'tools.catcher'
  );

	class RaspHashUnrecognizedTypeException extends RaspException { public $message = "Unrecognized type, method map work with arrays and objects"; }

	class RaspHash extends RaspAbstractType {

		public static function map($array, $field){
			try {
				$result = array();
				foreach($array as $key => $element) {
					if(is_array($element)) $result[] = $element[$field];
					elseif(is_object($element)) $result[] = $element->$field;
					else throw new RaspHashUnrecognizedTypeException;
				}
				return $result;
			} catch(RaspHashUnrecognizedTypeException $e) { RaspCatcher::add($e); }
		}
	}
?>
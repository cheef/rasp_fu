<?php

	require_once RASP_TYPES_PATH . 'abstract_type.php';
	require_once RASP_PATH . 'exception.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';

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
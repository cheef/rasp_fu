<?php

  rasp_lib(
    'types.abstract_type',
    'exception', 'tools.catcher'
  );

	class RaspHash extends RaspAbstractType {

		public static function map($array, $field){
			try {
				$result = array();
				foreach($array as $key => $element) {
					if(is_array($element)) $result[] = $element[$field];
					elseif(is_object($element)) $result[] = $element->$field;
					else throw new RaspException("Unrecognized type, method map work with arrays and objects");
				}
				return $result;
			} catch(RaspException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Getter for hash elements
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @param Any $returning
		 * @return Any
		 */
		public static function get($hash, $index_name, $returning = null) {
			return (isset($hash[$index]) ? $hash[$index] : $returning);
		}
	}
?>
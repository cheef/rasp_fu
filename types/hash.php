<?php

  rasp_lib(
    'types.abstract_type',
    'exception', 'tools.catcher'
  );

	/**
	 * Class for working with hashes
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 */
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

		/**
		 * Checks element of hash if not blank
		 * @param <type> $hash
		 * @param <type> $index_name
		 * @return <type>
		 */
		public static function is_not_blank($hash, $index_name){
			return !self::is_blank($hash, $index_name);
		}

		/**
		 * Checks element of hash if blank
		 * @param Hash $hash
		 * @param String $index_name
		 * @return Boolean
		 */
		public static function is_blank($hash, $index_name){
			if(!isset($hash[$index_name])) return true;
			if(empty($hash[$index_name]) && $hash[$index_name] != false && $hash[$index_name] != 0 && $hash[$index_name] != '0') return true;
			return false;
		}
	}
?>
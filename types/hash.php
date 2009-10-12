<?php

	/**
	 * This class provides functionality to work with hashes
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 * $Id$
	 */

	rasp_lib(
		'types.abstract_type', 'types.array',
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
		 * Get element from hash by index name
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @param Any $returning
		 * @return Any
		 */
		public static function get($hash, $index_name, $returning = null) {
			return RaspArray::get($hash, $index_name, $returning);
		}

		/**
		 * Get hash keys
		 * @param Hash $hash
		 * @return Array
		 */
		public static function keys($hash) {
			return RaspArray::keys($hash);
		}

		/**
		 * Get first element of hash or $returning if it empty
		 * @param Hash $hash
		 * @param Any $returning
		 * @return Any
		 */
		public static function first($hash, $returning = null) {
			return RaspArray::first($hash, $returning);
		}

		/**
		 * Delete hash element by index
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @return Any
		 */
		public static function delete(&$hash, $index_name){
			return RaspArray::delete($hash, $index_name);
		}

		/**
		 * Checks hash element if blank
		 * 
		 * Element blank if it:
		 * NULL
		 * not exists
		 *
		 * @param Hash $hash
		 * @param String $index_name
		 * @return Boolean
		 */
		public static function is_blank($hash, $index_name){
			if (!array_key_exists($index_name, $hash) || $hash[$index_name] === null) return true;
			return false;
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
		 * Check hash element if empty
		 *
		 * Element empty if it:
		 * ""      (an empty string)
		 * 0       (0 as an integer)
		 * "0"     (0 as a string)
		 * NULL
		 * FALSE
		 * array() (an empty array)
		 * not exists
		 *
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @return Boolean
		 */
		public static function is_empty($hash, $index_name) {
			if (!array_key_exists($index_name, $hash)) return true;
			return empty($hash[$index_name]);
		}

		/**
		 * Check hash element if not empty
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @return Boolean
		 */
		public static function is_not_empty($hash, $index_name) {
			return !self::is_empty($hash, $index_name);
		}

		/**
		 * Merge hashes
		 * @TODO make recursive merge
		 * @return Hash
		 */
		public static function merge(){
			try {
				$hashes = func_get_args();
				if(count($hashes) < 2) throw new RaspException('Not enought arguments to merge');

				$merged = self::delete($hashes, 0);
				foreach ($hashes as $hash) $merged = array_merge($merged, $hash);

				return $merged;
			} catch (RaspException $e) { RaspCatcher::add($e); };
		}

		/**
		 * Check hash element if true
		 * @param Hash $hash
		 * @param String || Integer $index_name
		 * @return Bolean 
		 */
		public static function is_true($hash, $index_name){
			return self::is_not_empty($hash, $index_name) && $hash[$index_name] === true;
		}
	}
?>
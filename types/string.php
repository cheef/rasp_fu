<?php

	require_once RASP_TYPES_PATH . 'abstract_type.php';

	class RaspString extends RaspAbstractType {

		private static $vowels = array('a', 'e', 'y', 'i', 'o', 'u');
		private static $exception_symbol = 'y';

		public static function underscore($string) {
			return strtolower(preg_replace('/(?<=\w)([A-Z])(?=[a-z])/', '_\\1', $string));
		}

		public static function humanize($string) {
			return ucwords(str_replace('_', ' ', $string));
		}

		public static function pluralize($string){
			return (self::is_exception(self::last($string)) ? substr($string , 0, count($string) - 2) . 'i' : $string) . (self::is_vowel(self::last($string)) ? 'es' : 's');
		}

		public static function tableize($string){
			$string = self::underscore($string);
			return self::pluralize($string);
		}

		public static function last($string){
			return $string[strlen($string) - 1];
		}

		private static function is_vowel($symbol){
			return in_array($symbol, self::$vowels);
		}

		public static function is_exception($symbol){
			return $symbol == self::$exception_symbol;
		}
	}
?>
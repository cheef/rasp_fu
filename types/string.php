<?php

  rasp_lib(
    'types.abstract_type'
  );

	class RaspString extends RaspAbstractType {

		private static $vowels = array('a', 'e', 'y', 'i', 'o', 'u');
		private static $exception_symbol = 'y';

		public function __construct($source){
			$this->source = $source;
		}

		public static function underscore($string) {
			return strtolower(preg_replace('/(?<=\w)([A-Z])(?=[a-z])/', '_\\1', $string));
		}

		public static function humanize($string) {
			return ucwords(str_replace('_', ' ', $string));
		}

		public static function pluralize($string){
			return (self::is_exception(self::last($string)) ? substr($string , 0, count($string) - 2) . 'i' : $string) . self::make_closure($string);
		}

		public static function tableize($string){
			return self::pluralize(self::underscore($string));
		}

		public static function last($string){
			return $string[strlen($string) - 1];
		}

		private static function is_vowel($symbol){
			return in_array($symbol, self::$vowels);
		}

		private static function make_closure($string){
			return (self::is_vowel(self::last($string)) ? 'es' : 's');
		}

		public static function is_exception($symbol){
			return $symbol == self::$exception_symbol;
		}

		public static function escape($string, $symbols = array("'")){
			foreach($symbols as $symbol) $string = str_replace($symbol, "\\" . $symbol, $string);
			return $string;
		}

		public static function create($source){
			return new RaspString($source);
		}

		public function to_s(){
			return $this->source;
		}

		public static function replace_all($string, $masks){
			foreach($masks as $mask => $replacement){
				if(preg_match("'^/(.+)/(\w)*$'", $mask, $matches)) $string = preg_replace($mask, $replacement, $string);
				else $string = str_replace($mask, $replacement, $string);
			}
			return $string;
		}
	}
?>
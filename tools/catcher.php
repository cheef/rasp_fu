<?php
	class RaspCatcher {
		public static $exceptions = array();

		public function add($exception, $die = true){
			self::$exceptions[] = $exception;
			if($die) die(self::show());
		}

		private static function show(){
				print "<pre>";
				foreach(self::$exceptions as $exception) {
					print $exception->getMessage() . " : throwed in \"" . $exception->getFile() . "\" [line " . $exception->getLine() . "]<br />";
					print_r($exception->getTraceAsString());
				}
				print "</pre>";
		}
	}
?>
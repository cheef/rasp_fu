<?php
	class RaspCatcher {
		public static $exceptions = array();
		public static $default_options = array('die' => true);

		public function add($exception, $options = array()){
			$options = array_merge(self::$default_options, $options);
			self::$exceptions[] = $exception;
			if($options['die']) die(self::show());
			else return false;
		}

		private static function show(){
				print "<pre>";
				foreach(self::$exceptions as $exception) {
					print $exception->getMessage() . " : throwed in \"" . $exception->getFile() . "\" [line " . $exception->getLine() . "]<br />";
					if($exception->has_additional())
						print "[" . $exception->error_number . "] " . $exception->error_message . "<br />" . '"' . $exception->query . "\"<br />";
					print_r($exception->getTraceAsString());
				}
				print "</pre>";
		}
	}
?>
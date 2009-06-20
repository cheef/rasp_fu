<?php

  rasp_lib(
    'types.array',
    'resources.abstract_resource'
  );

	class RaspMemory extends RaspAbstractResource {

		public static $options = array(
			'silence' => false
		);

		public static function free(&$variable){
			if (method_exists($variable,'__destruct')) $variable->__destruct();
			$variable = null;
			return true;
		}

		public static function show($options = array()){
			self::set($options);
			if(!self::is_silence()) print "Memory Usage: " . memory_get_usage();
		}

		public static function set($options){
			foreach($options as $option => $value) self::$options[$option] = $value;
		}

		public static function is_silence(){
			return RaspArray::index(self::$options, 'silence', false);
		}
	}
?>
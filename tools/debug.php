<?php

  rasp_lib(
    'types.array',
    'tools.abstract_tool'
  );

	class RaspDebug extends RaspAbstractTool {

		public static function show($variable){
			$backtrace = RaspArray::first(debug_backtrace());
			print '<strong>' . $backtrace['file'] . '</strong>&nbsp;(line <strong>' . $backtrace['line'] . '</strong>)';
			print '<pre>';
			print_r($variable);
			print '</pre>';
			return true;
		}
	}
?>
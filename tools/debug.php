<?php
	require_once RASP_TOOLS_PATH . 'abstract_tool.php';
	require_once RASP_TYPES_PATH . 'array.php';

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
<?php

  rasp_lib(
    'types.array',
    'tools.abstract_tool'
  );

	/**
	 * Class for simple debugging
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 */
	class RaspDebug extends RaspAbstractTool {

		/**
		 * Show any variable in html
		 * @param Any $variable
		 * @param String $backtrace
		 */
		public static function show($variable, $backtrace = null){
			$backtrace = RaspArray::first(empty($backtrace) ? debug_backtrace() : $backtrace);
			print '<strong>' . $backtrace['file'] . '</strong>&nbsp;(line <strong>' . $backtrace['line'] . '</strong>)';
			print '<pre>';
			var_dump($variable);
			print '</pre>';
		}
	}

/**
 * Helper function for debugging
 */
if (!function_exists('debug')) {
	function debug($variable) {
		return RaspDebug::show($variable, debug_backtrace());
	}
}
?>
<?php

	require_once RASP_CONTROLLERS_PATH . 'abstract_controller.php';

	class RaspController extends RaspAbstractController {

		protected $params, $cookies, $sessions;

		public function redirect_back_or_default(){
			header('Location: ' . RaspArray::index($_SERVER, 'HTTP_REFER', '/'));
		}
	}
?>
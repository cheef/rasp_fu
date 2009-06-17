<?php

	require_once RASP_CONTROLLERS_PATH . 'abstract_controller.php';

	class RaspController extends RaspAbstractController {

		protected $cookies, $sessions;
		public $params;

		public function __construct(){
			$this->params = $_POST;
			$this->cookies = $_COOKIE;
			$this->sessions = $_SESSION;
			$this->method = $_SERVER['REQUEST_METHOD'];
		}

		protected function render($partial_name, $variables = array()){
			extract($variables, EXTR_OVERWRITE);
			$partial = include TPL_DIR . $partial_name;
			exit;
		}

		protected function redirect_to($url){
			header('Location: ' . $url);
		}

		protected function redirect_back_or_default(){
			$this->redirect_to(RaspArray::index($_SERVER, 'HTTP_REFERER', '/'));
		}

		protected function params(){
			return $this->params;
		}

		protected function flash($name, $value){
			return $_SESSION[$name] = $value;
		}
	}
?>
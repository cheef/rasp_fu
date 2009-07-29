<?php

  rasp_lib(
    'controller.abstract_controller'
  );

	class RaspController extends RaspAbstractController {

		protected $cookies, $sessions;
		public $params;

		public function __construct(){
			$this->params = $_POST;
			$this->cookies = $_COOKIE;
			$this->sessions = $_SESSION;
			$this->method = $_SERVER['REQUEST_METHOD'];
		}

		public function render($options, $variables = array()){
			if(RaspArray::is_not_empty($options, 'layout')) {
				$center = $this->include_template(TPL_DIR . $options['partial'], $variables);
				$template = $this->include_template(TPL_DIR . $options['layout'], array('center' => $center));
			} else $template = $this->include_template(TPL_DIR . $options['partial'], $variables);
			print $template;
		}

		public function include_template($path, $variables = array()){
			ob_start();
			extract($variables, EXTR_OVERWRITE);
			include $path;
			$template = ob_get_contents();
			ob_end_clean();
			return $template;
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
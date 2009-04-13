<?php

	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_TOOLS_PATH . 'http_response.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspHttpRequester extends RaspAbstractService {
		public static $default_requester_options = array('port' => 80);
		public $handler;
		private $options = array();

		public function RaspHttpRequester($options = array()){
			$request_options = array_merge(self::$default_requester_options, $options);
			$this->handler = curl_init();

			$this->set(array(
				CURLOPT_PORT => $request_options['port'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true
			));

			if(RaspArray::index($request_options, 'post', false)){
				$this->set(array(CURLOPT_POST => 1));
				if(RaspArray::index($request_options, 'data', false)) $this->set(array(CURLOPT_POSTFIELDS => $request_options['data']));
			}
			if(RaspArray::index($request_options, 'cookies', false)) $this->set(array(CURLOPT_COOKIE => $request_options['cookies']));
		}

		public function send($url){
			$this->set(array(CURLOPT_URL => $url));
			return RaspHttpResponse::create(array('source' => $this->request(), 'info' => curl_getinfo($this->handler)));
		}

		private function request(){
			curl_setopt_array($this->handler, $this->options);
			return curl_exec($this->handler);
		}

		public function set($options){
			return $this->options = $this->options + $options;
		}

		public function close(){
			curl_close($this->handler);
			$this->__destruct();
		}

		public static function create($url, $options = array()){
			$request = new RaspHttpRequester($options);
			$returning = $request->send($url);
			$request->close();
			return $returning;
		}
	}
?>
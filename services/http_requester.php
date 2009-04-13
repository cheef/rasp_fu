<?php

	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_TOOLS_PATH . 'http_response.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspHttpRequester extends RaspAbstractService {
		public static $default_requester_options = array('port' => 80);
		public $handler;

		public function RaspHttpRequester($options = array()){
			$request_options = array_merge(self::$default_requester_options, $options);
			$this->handler = curl_init();
			curl_setopt($this->handler, CURLOPT_PORT, $request_options['port']);
			curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->handler, CURLOPT_HEADER, true);
			if(RaspArray::index($request_options, 'post', false)){
				curl_setopt($this->handler, CURLOPT_POST, 1);
				if(RaspArray::index($request_options, 'data', false)) curl_setopt($this->handler, CURLOPT_POSTFIELDS, $request_options['data']);
			}
			if(RaspArray::index($request_options, 'cookies', false)) curl_setopt($this->handler, CURLOPT_COOKIE, $request_options['cookies']);
		}

		public function send($url){
			curl_setopt($this->handler, CURLOPT_URL, $url);
			$this->response_body = curl_exec($this->handler);
			return RaspHttpResponse::create(array('source' => $this->response_body, 'info' => curl_getinfo($this->handler)));
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
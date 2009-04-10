<?php

	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_TOOLS_PATH . 'http_response.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspHttpRequester extends RaspAbstractService {
		public $handler;

		public function RaspHttpRequester($options = array()){
			$this->handler = curl_init();
			curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->handler, CURLOPT_HEADER, true);
			if(RaspArray::index($options, 'post', false)){
				curl_setopt($this->handler, CURLOPT_POST, 1);
				if(RaspArray::index($options, 'data', false)) curl_setopt($this->handler, CURLOPT_POSTFIELDS, $options['data']);
			}
			if(RaspArray::index($options, 'cookies', false)) curl_setopt($this->handler, CURLOPT_COOKIE, $options['cookies']);
		}

		public function send($url){
			curl_setopt($this->handler, CURLOPT_URL, $url);
			$this->response_body = curl_exec($this->handler);
			return RaspHttpResponse::create(array('source' => $this->response_body, 'header_size' => curl_getinfo($this->handler, CURLINFO_HEADER_SIZE)));
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
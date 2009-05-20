<?php

	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_TOOLS_PATH . 'http_response.php';
	require_once RASP_TOOLS_PATH . 'http_header.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

	class RaspCurlInitException extends RaspException { public $message = "Curl initialization failed"; }
	class RaspCurlExecutingException extends RaspException { public $message = "Curl executing failed"; }

	class RaspHttpRequester extends RaspAbstractService {
		public static $default_requester_options = array('port' => 80, 'timeout' => 60);
		public $handler, $get_data = "";
		public $options = array();

		public function __construct($options = array()){
			$request_options = array_merge(self::$default_requester_options, $options);

			try { if(!$this->handler = curl_init()) throw new RaspCurlInitException; }
			catch(RaspCurlInitException $e) { RaspCatcher::add($e); }

			$this->set(array(
				CURLOPT_PORT => RaspArray::delete($request_options, 'port'),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => RaspArray::delete($request_options, 'timeout'),
				CURLOPT_HEADER => true,
				CURLOPT_AUTOREFERER => true
			));

			if(RaspArray::index($request_options, 'auth_basic', false)) {
				$this->set(array(CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
				$this->set(array(CURLOPT_USERPWD => $request_options['auth_basic']));
			}

			if(RaspArray::index($request_options, 'redirect', false)) $this->set(array(CURLOPT_FOLLOWLOCATION => true));

			if(RaspArray::index($request_options, 'headers', false))
				$this->set(array(CURLOPT_HTTPHEADER => RaspHttpHeader::create(array('attributes' => RaspArray::delete($request_options, 'headers')))->to_curl_strings()));

			if(RaspArray::index($request_options, 'post', false)){
				$this->set(array(CURLOPT_POST => 1));
				if(RaspArray::index($request_options, 'data', false)) $this->set(array(CURLOPT_POSTFIELDS => RaspArray::delete($request_options, 'data')));
			} elseif(RaspArray::index($request_options, 'data', false)) $this->get_data = RaspArray::delete($request_options, 'data');

			if(RaspArray::index($request_options, 'cookies', false)) $this->set(array(CURLOPT_COOKIE => RaspArray::delete($request_options, 'cookies')));
		}

		public function send($url){
			$this->set(array(CURLOPT_URL => $url . (empty($this->get_data) ? '' : '?' . $this->get_data)));
			try {
				if(!($response = $this->request())) throw new RaspCurlExecutingException;
				return RaspHttpResponse::create(array('source' => $response, 'info' => curl_getinfo($this->handler)));
			} catch(RaspCurlExecutingException $e) { RaspCatcher::add($e); }
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
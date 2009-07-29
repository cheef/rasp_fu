<?php

  rasp_lib(
    'types.array', 'types.hash',
    'tools.http_response', 'tools.http_header',
    'services.abstract_service',
    'exception', 'tools.catcher'
  );

	class RaspHttpRequester extends RaspAbstractService {

		public static $default_requester_options = array('port' => 80, 'timeout' => 60);

		/**
		 * Curl resource
		 * @var Resource
		 */
		private $handler = null;

		private $get_data = '';

		/**
		 * Request options
		 * @var Hash
		 */
		public $options = array();

		/**
		 * Initializing and sending request
		 * @param String $url
		 * @param Array $options
		 * @return RaspHttpResponse
		 */
		public static function create($url, $options = array()){
			$request = RaspHttpRequester::initialize($options);
			$returning = $request->send($url);
			$request->close();
			return $returning;
		}

		/**
		 * Static constructor
		 * @param Hash $options
		 * @return RaspHttpRequester
		 */
		public static function initialize($options = array()) {
			return new RaspHttpRequester($options);
		}

		/**
		 * Constructing request
		 * @param Hash $options
		 */
		public function __construct($options = array()){
			$request_options = array_merge(self::$default_requester_options, $options);

			try {
				if(!$this->handler = curl_init()){
					throw new RaspException('CURL error #' . curl_errno($this->handler) . ' - ' . curl_error($this->handler));
				}
			} catch(RaspException $e) { RaspCatcher::add($e); }

			$this->set(array(
				CURLOPT_PORT => RaspArray::delete($request_options, 'port'),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => RaspArray::delete($request_options, 'timeout'),
				CURLOPT_HEADER => true,
				CURLOPT_AUTOREFERER => true
			));

			if(RaspHash::get($request_options, 'auth_basic', false)) {
				$this->set(array(CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
				$this->set(array(CURLOPT_USERPWD => $request_options['auth_basic']));
			}

			if(RaspHash::get($request_options, 'redirect', false)) $this->set(array(CURLOPT_FOLLOWLOCATION => true));

			if(RaspHash::get($request_options, 'headers', false))
				$this->set(array(CURLOPT_HTTPHEADER => RaspHttpHeader::create(array('attributes' => RaspArray::delete($request_options, 'headers')))->to_curl_strings()));

			if(RaspHash::get($request_options, 'post', false)){
				$this->set(array(CURLOPT_POST => 1));
				if(RaspHash::get($request_options, 'data', false)) $this->set(array(CURLOPT_POSTFIELDS => RaspArray::delete($request_options, 'data')));
			} elseif(RaspHash::get($request_options, 'data', false)) $this->get_data = RaspArray::delete($request_options, 'data');

			if(RaspHash::get($request_options, 'cookies', false)) $this->set(array(CURLOPT_COOKIE => RaspArray::delete($request_options, 'cookies')));
		}

		/**
		 * Sending request and returning response
		 * @param String $url
		 * @return RaspHttpResponse
		 */
		public function send($url){
			$this->set(array(CURLOPT_URL => trim($url . (empty($this->get_data) ? '' : '?' . $this->get_data))));
			
			try {
				if(!($response = $this->request())) {
					throw new RaspException('CURL error #' . curl_errno($this->handler) . ' - ' . curl_error($this->handler));
				}
				return RaspHttpResponse::create(array('source' => $response, 'info' => curl_getinfo($this->handler)));
			} catch(RaspException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Closing curl handler and destruct self
		 */
		public function close(){
			curl_close($this->handler);
			$this->__destruct();
		}		

		/**
		 * Add options
		 * @param Hash $options
		 * @return Hash
		 */
		private function set($options){
			return $this->options = $this->options + $options;
		}

		/**
		 * Execute request
		 * @return Boolean || String
		 */
		private function request(){
			curl_setopt_array($this->handler, $this->options);
			return curl_exec($this->handler);
		}
	}
?>
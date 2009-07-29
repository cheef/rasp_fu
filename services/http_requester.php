<?php

  rasp_lib(
    'types.array', 'types.hash',
    'tools.http_response', 'tools.http_header',
    'services.abstract_service',
    'exception', 'tools.catcher'
  );

	/**
	 * Class for simply request pages uses curl
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 */
	class RaspHttpRequester extends RaspAbstractService {

		/**
		 * Default request options, can be edited
		 * @var Hash
		 */
		public static $default_requester_options = array('timeout' => 60);

		/**
		 * Curl resource
		 * @var Resource
		 */
		private $handler = null;

		/**
		 * Request options
		 * @var Hash
		 */
		private $options = array();

		/**
		 * Request (query string)
		 * @var String
		 */
		private $data = null;

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
			try {
				if(!$this->handler = curl_init()){
					throw new RaspException('CURL error #' . curl_errno($this->handler) . ' - ' . curl_error($this->handler));
				}
				$this->configure($options);
			} catch(RaspException $e) { RaspCatcher::add($e); }
		}

		/**
		 * Setup request params
		 * @param Hash $options
		 */
		private function configure($options = array()){
			$request_options = RaspHash::merge(self::$default_requester_options, $options);

			$this->set(array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => RaspHash::delete($request_options, 'timeout'),
				CURLOPT_HEADER => true,
				CURLOPT_AUTOREFERER => true
			));

			/**
			 * Port
			 */
			if(RaspHash::is_not_empty($request_options, 'port'))
				$this->set(array(CURLOPT_PORT => RaspHash::delete($request_options, 'port')));

			/**
			 * Basic authentification
			 */
			if(RaspHash::is_not_empty($request_options, 'auth_basic')) {
				$this->set(array(CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
				$this->set(array(CURLOPT_USERPWD => RaspHash::delete($request_options, 'auth_basic')));
			}

			/**
			 * Follow redirect
			 */
			if(RaspHash::is_not_blank($request_options, 'redirect') && RaspHash::delete($request_options, 'redirect') === true)
				$this->set(array(CURLOPT_FOLLOWLOCATION => true));

			/**
			 * Set custom headers
			 */
			if(RaspHash::is_not_blank($request_options, 'headers'))
				$this->set(array(CURLOPT_HTTPHEADER => RaspHttpHeader::create(array(
					'attributes' => RaspHash::delete($request_options, 'headers')))->to_curl_strings())
				);

			/**
			 * Post request
			 */
			if(RaspHash::is_not_blank($request_options, 'post') && $request_options['post'] === true)
				$this->set(array(CURLOPT_POST => 1));

			/**
			 * Request data
			 */
			if(RaspHash::is_not_empty($request_options, 'data')){
				if(RaspHash::is_not_blank($request_options, 'post') && $request_options['post'] === true){
					$this->set(array(CURLOPT_POSTFIELDS => RaspHash::delete($request_options, 'data')));
				} else {
					$this->data = RaspHash::delete($request_options, 'data');
				}
			}

			/**
			 * Cookies
			 */
			if(RaspHash::is_not_blank($request_options, 'cookies'))
				$this->set(array(CURLOPT_COOKIE => RaspHash::delete($request_options, 'cookies')));
		}
		/**
		 * Sending request and returning response
		 * @param String $url
		 * @return RaspHttpResponse
		 */
		public function send($url){
			$this->set(array(CURLOPT_URL => trim($url . (empty($this->data) ? '' : '?' . $this->data))));
			
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
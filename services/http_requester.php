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
		 * Exceptions
		 */
		const EXCEPTION_WRONG_PORT = 'Wrong port for request';

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
				if (!$this->handler = curl_init()) {
					throw new RaspException('CURL error #' . curl_errno($this->handler) . ' - ' . curl_error($this->handler));
				}
				$this->configure($options);
			} catch(RaspException $e) { RaspCatcher::add($e); }
		}		

		/** Constructors healpers block */

		/**
		 * Set port request port
		 * @param Hash $options
		 * @return Hash
		 */
		private function port(&$options) {
			if (RaspHash::is_not_empty($options, 'port')) {
				$port = RaspHash::delete($options, 'port');
				if (!is_numeric($port)) throw RaspException(self::EXCEPTION_WRONG_PORT);
				return $this->set(array(CURLOPT_PORT => $port));
			}
		}

		/**
		 * Follow redirect
		 * @param Hash $options
		 * @return Hash
		 */
		private function follow_redirect(&$options) {
			if (RaspHash::is_not_blank($options, 'redirect') && RaspHash::delete($options, 'redirect') === true) {
				return $this->set(array(CURLOPT_FOLLOWLOCATION => true));
			}
		}

		/**
		 * Set custom headers
		 * @param Hash $options
		 * @return Hash
		 */
		private function headers(&$options) {
			if (RaspHash::is_not_empty($options, 'headers')) {
				$headers = RaspHash::delete($options, 'headers');

				if (is_array($headers)) {
					$headers = RaspHttpHeader::create(array('attributes' => $headers))->to_curl_strings();
				}

				return $this->set(array(CURLOPT_HTTPHEADER => $headers));
			}
		}

		/**
		 * Set post type for request
		 * @param Hash $options
		 * @return Hash
		 */
		private function post(&$options) {
			if (RaspHash::is_not_blank($options, 'post') && $options['post'] === true) {
				return $this->set(array(CURLOPT_POST => 1));
			}
		}

		/**
		 * Set basic authorization params
		 * @param Hash $options
		 * @return Hash
		 */
		private function basic_auth(&$options) {
			if (RaspHash::is_not_empty($options, 'auth_basic')) {
				$hashed_auth_params = RaspHash::delete($options, 'auth_basic');
				$this->set(array(CURLOPT_HTTPAUTH => CURLAUTH_BASIC));
				return $this->set(array(CURLOPT_USERPWD  => $hashed_auth_params));
			}
		}

		/**
		 * Set content data to request, post or get
		 * @param Hash $options
		 * @return Hash
		 */
		private function content(&$options) {
			if (RaspHash::is_not_empty($options, 'data')) {
				$content = RaspHash::delete($options, 'data');
				if (!is_array($content)) {
					if (!isset($options['headers'])) $options['headers'] = array();
					$options['headers'] = RaspHash::merge($options['headers'], array('Content-length' => strlen($content)));
				}

				if (RaspHash::is_not_blank($options, 'post') && $options['post'] === true) {
					return $this->set(array(CURLOPT_POSTFIELDS => $content));
				}
					
				return $this->data = $content;
			}
		}

		/**
		 * Set cookies to request headers
		 * @param Hash $options
		 * @return Hash
		 */
		private function cookies(&$options) {
			if (RaspHash::is_not_empty($options, 'cookies')) {
				return $this->set(array(CURLOPT_COOKIE => RaspHash::delete($options, 'cookies')));
			}
		}

		/**
		 * Set required and nessary options
		 * @param Hash $options
		 * @return Hash
		 */
		private function system(&$options) {
			return $this->set(array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => RaspHash::delete($options, 'timeout'),
				CURLOPT_HEADER         => true,
				CURLOPT_AUTOREFERER    => true
			));
		}

		/** Request methods */

		/**
		 * Setup request params
		 * @param Hash $options
		 */
		private function configure($options = array()){
			$options = RaspHash::merge(self::$default_requester_options, $options);
			
			$this->system($options);

			$this->port($options);

			$this->basic_auth($options);

			$this->follow_redirect($options);
			
			$this->content($options);

			$this->post($options);

			$this->cookies($options);

			$this->headers($options);

			return true;
		}

		/**
		 * Initializing and sending request
		 * @param String $url
		 * @param Array $options
		 * @return RaspHttpResponse
		 */
		public static function create($url, $options = array()){
			$request   = RaspHttpRequester::initialize($options);
			$returning = $request->send($url);
			$request->close();
			return $returning;
		}

		/**
		 * Sending request and returning response
		 * @param String $url
		 * @return RaspHttpResponse
		 */
		public function send($url){
			$this->set(array(CURLOPT_URL => trim($url . (empty($this->data) ? '' : '?' . $this->data))));
			
			try {
				if (!($response = $this->request())) {
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
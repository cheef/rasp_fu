<?php

  rasp_lib(
    'types.array',
    'tools.abstract_tool', 'tools.http_header'
  );

	/**
	 * Curl response helper class
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 */
	class RaspHttpResponse extends RaspAbstractTool {

		public $body, $header, $info, $status, $url;
		public static $current = null;

		/**
		 * Constructor
		 * @param String $response_body
		 * @param Hash $options
		 */
		public function __construct($response_body, $options = array()){
			$this->info			= $options['info'];
			$this->url			= $this->info['url'];
			$this->status		= $this->info['http_code'];
			$this->body			= trim(substr($response_body, $this->info['header_size']));
			$this->header		= $this->extract_header(substr($response_body, 0, $this->info['header_size']));						
		}

		/**
		 * Check success status of response
		 * @return Boolean
		 */
		public function is_success(){
			return $this->status == 200;
		}

		/**
		 * Construct response and save in to static variable
		 * @param <type> $options
		 * @return <type>
		 */
		public static function create($options){
			return (self::$current = self::initialize($options));
		}

		/**
		 * Construct response
		 * @param Hash $options
		 * @return RaspHttpResponse
		 */
		public static function initialize($options = array()){
			return new RaspHttpResponse(RaspArray::delete($options, 'source'), $options);
		}

		/**
		 * Parse response to divide headers and body
		 * @param String $response_body
		 * @return Hash
		 */
		private function extract_header($response_body){
			return RaspHttpHeader::create(array('source' => $response_body))->to_a();
		}
	}
?>
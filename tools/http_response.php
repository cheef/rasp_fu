<?php
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_TOOLS_PATH . 'abstract_tool.php';
	require_once RASP_TOOLS_PATH . 'http_header.php';

	class RaspHttpResponse extends RaspAbstractTool {

		public $response, $body, $header;
		public static $current = null;

		public function RaspHttpResponse($response_body, $options = array()){
			$this->response = $response_body;
			$this->body = trim(substr($response_body, $options['header_size']));
			$this->header = $this->extract_header(substr($response_body, 0, $options['header_size']));
		}

		public static function create($options){
			return (self::$current = new RaspHttpResponse(RaspArray::delete($options, 'source'), $options));
		}

		private function extract_header($response_body){
			return RaspHttpHeader::create($response_body)->to_a();
		}

		public function __destruct(){
			$this->response = null;
			$this->body = null;
			$this->header = null;
		}
	}
?>
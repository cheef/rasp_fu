<?php

  rasp_lib(
    'types.array',
    'tools.abstract_tool', 'tools.http_header'
  );

	class RaspHttpResponse extends RaspAbstractTool {

		public $response, $body, $header, $info, $status;
		public static $current = null;

		public function RaspHttpResponse($response_body, $options = array()){
			$this->response = $response_body;
			$this->info = $options['info'];
			$this->status = $this->info['http_code'];
			$this->body = trim(substr($response_body, $this->info['header_size']));
			$this->header = $this->extract_header(substr($response_body, 0, $this->info['header_size']));
		}

		public function is_success(){
			return $this->status == 200;
		}

		public static function create($options){
			return (self::$current = new RaspHttpResponse(RaspArray::delete($options, 'source'), $options));
		}

		private function extract_header($response_body){
			return RaspHttpHeader::create(array('source' => $response_body))->to_a();
		}
	}
?>
<?php
	class RaspHttpRequestBuilder {

		public static $break = "\r\n";

		public function __construct($options){
			foreach($options as $option => $value) $this->set($option, $value);
		}

		public function set($attribute, $value){
			$this->$attribute = $value;
		}

		public static function create($options){
			$header = new RaspHttpRequestBuilder($options);
			return $header->build();
		}

		private function build(){
			$this->add_content_headers();

			$header = join(self::$break, $this->build_header());
			return $header . self::$break . self::$break . $this->content;
		}

		private function build_header($header = array()){
			$header[] = $this->method . ' ' . $this->url . ' ' . $this->protocol;
			foreach($this->attributes as $attribute => $value) $header[] = $attribute . ': ' . $value;
			return $header;
		}

		public function add_content_headers(){
			if($this->is_post()){
				$this->attributes['Content-Length'] = strlen($this->content);
				$this->attributes['Content-Type'] = $this->content_type;
			}
		}

		public function is_get(){
			return $this->is_request_method('GET');
		}

		public function is_post(){
			return $this->is_request_method('POST');
		}

		private function is_request_method($method){
			return strtoupper($this->method) == $method;
		}
	}
?>
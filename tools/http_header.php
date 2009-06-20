<?php

  rasp_lib(
    'types.array',
    'tools.abstract_tool', 'tools.http_request_builder'
  );

	class RaspHttpHeader extends RaspAbstractService {

		public $attributes = array(), $url, $method, $protocol, $content, $content_type, $request;

		public static $default_options = array('method' => 'GET', 'url' => 'http://127.0.0.1', 'protocol' => 'HTTP/1.1', 'content_type' => 'text/html; charset=UTF-8');

		public function __construct($options){
			$options = array_merge(self::$default_options, $options);
			if(RaspArray::index($options, 'source', false)) $this->parse(RaspArray::delete($options, 'source'));
			foreach($options as $option => $value) $this->$option = $value;
			if(RaspArray::index($options, 'build', false))
				$this->request = RaspHttpRequestBuilder::create(array(
					'attributes' => $this->attributes,
					'content' => $this->content,
					'url' => $this->url,
					'method' => $this->method
				));
		}

		private function parse($source){
			$matches = array();
			preg_match_all('/([a-zA-Z-]*):(.*?)\\r\\n/is', $source, $matches);
			foreach(RaspArray::second($matches) as $key => $attribute) $this->attributes[$attribute] = $matches[2][$key];
		}

		public function to_a(){
			return $this->attributes;
		}

		public function to_curl_strings(){
			$header = array();
			foreach($this->attributes as $attribute => $value) $header[] = $attribute . ': ' . $value;
			return $header;
		}

		public static function create($options){
			return new RaspHttpHeader($options);
		}
	}
?>
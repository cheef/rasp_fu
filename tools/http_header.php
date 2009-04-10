<?php
	require_once RASP_TOOLS_PATH . 'abstract_tool.php';

	class RaspHttpHeader extends RaspAbstractService {

		public $attributes = array();

		public function RaspHttpHeader($source){
			$matches = array();
			preg_match_all('/([a-zA-Z-]*):(.*?)\\r\\n/is', $source, $matches);
			foreach(RaspArray::second($matches) as $key => $attribute) $this->attributes[$attribute] = $matches[2][$key];
		}

		public function to_a(){
			return $this->attributes;
		}

		public static function create($source){
			return new RaspHttpHeader($source);
		}
	}
?>
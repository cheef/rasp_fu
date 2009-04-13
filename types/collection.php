<?php

	require_once RASP_TYPES_PATH . 'abstract_type.php';
	require_once RASP_TYPES_PATH . 'array.php';

	class RaspCollection extends RaspAbstractType {

		public $data = null, $first = null, $last = null, $size;

		public function RaspCollection($array){
			$this->data = $array;
			$this->set_first();
			$this->set_last();
			$array = null;
		}

		public function size($forcing = false){
			return (($forcing || empty($this->size)) ? count($this->data) : $this->size);
		}

		public function add($element){
			$this->data[] = $element;
			return $this->set_last();
		}

		private function set_last(){
			$local_size = 1;
			foreach($this->data as $key => $element) {
				if($local_size == count($this->data)) return $this->last = &$this->data[$key];
				$local_size++;
			}
		}

		private function set_first(){
			foreach($this->data as $key => $element) return $this->first = &$this->data[$key];
		}

		public static function create($array){
			return new RaspCollection($array);
		}
	}

?>
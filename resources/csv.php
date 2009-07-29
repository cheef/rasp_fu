<?php

  rasp_lib(
    'types.string',
    'resources.abstract_resource', 'resources.file'
  );

	class RaspCSV extends RaspAbstractResource {

		public static $delimiter = ';', $endcloser = "'";

		public $file = null;
		public $masks = array();

		public function __construct($options){
			$this->file = RaspFile::create($options);
		}

		public static function create($options){
			return new RaspCSV($options);
		}

		public function clear($masks){
			$this->masks = $masks;
			return $this;
		}

		public function write($data){
			if(is_array($data)){
				foreach($data as $key => $element){
					if($this->need_clear()) $element = RaspString::replace_all($element, $this->masks);
					$data[$key] = self::endclose($element);
				}
				return $this->file->write(join(self::$delimiter, $data) . "\n");
			} else return false;
		}

		private static function endclose($element){
			return self::$endcloser . $element . self::$endcloser;
		}

		private function need_clear(){
			return !empty($this->masks);
		}

		public function close(){
			$this->file->close();
			$this->__destruct();
		}
	}
?>
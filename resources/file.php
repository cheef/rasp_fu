<?php
	require_once RASP_RESOURCES_PATH . 'abstract_resource.php';
	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

	class RaspFileOpenException extends RaspException {	public $message = 'File not exist or permission denied';	}
	class RaspFileAccessException extends RaspException { public $message = "Can't write to file, permission denied"; }

	class RaspFile extends RaspAbstractResource {

		const DEFAULT_FILE_MOD = 'r';
		const DEFAULT_READ_BLOCK = 4096;

		public $handler = null, $source = null;

		public function __construct($options = array()){
			$this->initilize($options);
		}

		public function initilize($options){
			if(isset($options['source'])){
				$this->source = $options['source'];
				try {
					$this->handler = @fopen($this->source, RaspArray::index($options, 'mode', self::DEFAULT_FILE_MOD));
					if(!$this->handler) throw new RaspFileOpenException; else return $this->handler;
				} catch(RaspFileOpenException $e){ RaspCatcher::add($e); }
			} else return false;
		}

		public function write($data){
			try { if(!$returning = fwrite($this->handler, $data)) throw new RaspFileAccessException; return $returning;}
			catch(RaspFileAccessException $e) { RaspCatcher::add($e); }
		}

		public function close(){
			fclose($this->handler);
		}

		public function read($block = self::DEFAULT_READ_BLOCK){
			return fgets($this->handler, $block);
		}

		public function is_eof(){
			return feof($this->handler);
		}

		public static function create($options){
			return new RaspFile($options);
		}
	}
?>
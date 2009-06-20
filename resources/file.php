<?php

  rasp_lib(
    'types.array',
    'resources.abstract_resource',
    'exception', 'tools.catcher'
  );

	class RaspFileException extends RaspException {};

	class RaspFile extends RaspAbstractResource {

		const DEFAULT_FILE_MOD = 'r';
		const DEFAULT_READ_BLOCK = 4096;
		const DEFAULT_PERMISSION = 0755;
		const EXCEPTION_DELETED_FILE_NOT_EXIST = "File not exists, and can't be deleted";
		const EXCEPTION_FILE_DELETION = "Can't delete file, no permission";
		const EXCEPTION_FILE_DESTINATION_MISSED = "Can't copy, destination option missed";
		const EXCEPTION_FILE_ALREADY_EXIST = "Destination file is already exists";
		const EXCEPTION_FILE_WRITE_ACCESS = "Can't write to file, permission denied";
		const EXCEPTION_FILE_OPEN = "File not exist or permission denied";
		const EXCEPTION_FILE_SOURCE_MISSED = "File source option missed";
		const EXCEPTION_FILE_CHMOD = "Can't set file permissions";

		public $handler = null, $source = null, $size = null;

		public function __construct($options = array()){
			$this->initilize($options);
		}

		public function initilize($options){
			try {
				if(RaspArray::is_not_empty($options, 'source')){
					$this->source = $options['source'];
					$this->handler = @fopen($this->source, RaspArray::index($options, 'mode', self::DEFAULT_FILE_MOD));
					if(!$this->handler) throw new RaspFileException(self::EXCEPTION_FILE_OPEN); return $this->handler;
				} else throw new RaspFileException(self::EXCEPTION_FILE_SOURCE_MISSED);
			} catch(RaspFileException $e){ RaspCatcher::add($e); }
		}

		public function write($data){
			try { if(!$returning = fwrite($this->handler, $data)) throw new RaspFileException(self::EXCEPTION_FILE_WRITE_ACCESS); return $returning;}
			catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function close(){
			fclose($this->handler);
			$this->__destruct();
		}

		public function read($block = self::DEFAULT_READ_BLOCK){
			return fgets($this->handler, $block);
		}

		public function is_eof(){
			return feof($this->handler);
		}

		public function is_exists($file = null){
			if(!empty($file))
				#static method behavior#
			 	return file_exists($file);
			else
				#object method behavior#
				return file_exists($this->source);
		}

		public function copy($options = array()){
			try {
				if(RaspArray::is_empty($options, 'destination')) throw new RaspFileException(self::EXCEPTION_FILE_DESTINATION_MISSED);
				if(self::is_exists($options['destination']) && !RaspArray::index($options, 'rewrite', false)) throw new RaspFileException(self::EXCEPTION_FILE_ALREADY_EXIST);
				if(RaspArray::is_not_empty($options, 'target')){
					#static method behavior#
					return copy($options['target'], $options['destination']);
				} else {
					#object method behavior#
					$new_file = self::create(array('source' => $options['destination'], 'mode' => 'w'));
					$returning = $new_file->write($this->read_entire());
					$new_file->close();
					return $returning;
				}
			} catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function move($options = array()){
			try {
				if(RaspArray::is_empty($options, 'destination')) throw new RaspFileException(self::EXCEPTION_FILE_DESTINATION_MISSED);
				if(self::is_exists($options['destination'])) throw new RaspFileException(self::EXCEPTION_FILE_ALREADY_EXIST);
				if(RaspArray::is_not_empty($options, 'target'))
					#static method behavior#
					return self::copy($options) && self::delete(array('source' => $options['target']));
				else
					#object method behavior#
					return $this->copy($options) && $this->delete();
			} catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function size($options = array()){
			try {
				if(RaspArray::is_not_empty($options, 'source')) {
					#static method behavior#
					if(!self::is_exists($options['source'])) throw new RaspFileException(self::EXCEPTION_DELETED_FILE_NOT_EXIST);
				 	return filesize($options['source']);
				} else
					#object method behavior#
					return ((RaspArray::index($options, 'forcing', false) || empty($this->size)) ? $this->size = filesize($this->source) : $this->size);
			} catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function delete($options = array()){
			try {
				if(RaspArray::is_not_empty($options, 'target')){
					#static method behavior#
					if(!self::is_exists($options['target'])) throw new RaspFileException(self::EXCEPTION_DELETED_FILE_NOT_EXIST);
					if(!(@unlink($options['target']))) throw new RaspFileException(self::EXCEPTION_FILE_DELETION);
					else return true;
				} else {
					#object method behavior#
					fclose($this->handler);
					if(!$this->is_exists()) throw new RaspFileException(self::EXCEPTION_DELETED_FILE_NOT_EXIST);
					if(!(@unlink($this->source))) throw new RaspFileException(self::EXCEPTION_FILE_DELETION);
					$this->__destruct();
					return true;
				}
			} catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function chmod($mode = self::DEFAULT_PERMISSION){
			try {
				if(!chmod($this->source, $mode)) throw new RaspFileException(self::EXCEPTION_FILE_CHMOD);
				else return true;
			} catch(RaspFileException $e) { RaspCatcher::add($e); }
		}

		public function read_entire(){
			$data = '';
			while(!$this->is_eof()) $data .= $this->read();
			return $data;
		}

		public static function create($options){
			return new RaspFile($options);
		}
	}
?>
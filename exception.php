<?php
	class RaspException extends Exception {
		public $has_additional = false;

		public function has_additional(){
			return $this->has_additional;
		}
	}
?>
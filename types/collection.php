<?php

	/**
	 * Iteration
	 * @author Ivan Garmatenko <cheef.che@gmail.com>
	 */

	class RaspCollection implements Iterator {

		/**
		 * Data storage
		 * @var Array
		 */
		private $data = array();

		public function __construct($data = array()) {
			$this->data = $data;
		}

		/**
		 * Set the internal pointer of an array data to its first element
		 */
		public function rewind() {
			reset($this->data);
		}

		/**
		 * Return the current element in an data array
		 * @return Any
		 */
		public function current() {
			return current($this->data);
		}

		/**
		 * Returns the index element of the current data array position.
		 * @return Integer
		 */
		public function key() {
			return key($this->data);
		}

		/**
		 * Advance the internal array pointer of an array
		 * @return Any
		 */
		public function next() {
			return next($this->data);
		}

		/**
		 * Checks if cursor position is out of array
		 * @return Boolean
		 */
		public function valid() {
			return $this->current() !== false;
		}

		/**
		 * Checks if data array is empty
		 * @return Boolean
		 */
		public function is_empty() {
			$data = $this->data;
			return empty($data);
		}

		public function add($element) {
			$this->data[] = $element;
		}

		public function add_each($elements) {
			if (!is_array($elements)) throw new Exception('Wrong argument for method, expected Array but was ' . gettype($elements));
			return $this->data = array_merge($this->data, $elements);
		}

		public function first() {
			if ($this->is_empty()) return array();
			return $this->data[0];
		}

		public function last() {
			if ($this->is_empty()) return array();
			return $this->data[count($this->data) - 1];
		}

		public static function initialize($data = array()) {
			return new RaspCollection($data);
		}
	}
?>
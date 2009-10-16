<?php

/**
 * Collection of ActiveRecord objects
 * @author Ivan Garmatenko <cheef.che@gmail.com>
 */

rasp_lib('types.collection', 'types.hash');

class RaspActiveRecordCollection extends RaspCollection {
	
	private $connection, $request, $object_class;

	/**
	 * Elements storage
	 * @var Array
	 */
	private $data     = array();

	/**
	 * Storage for a fetching element
	 * @var Array || false
	 */
	private $storage  = array();

	/**
	 * Key
	 * @var Integer
	 */
	private $position = 0;

	/**
	 * Constructor
	 * @param Hash $options
	 */
	public function __construct($options = array()) {

		$this->connection   = RaspHash::get($options, 'connection');
		$this->request      = RaspHash::get($options, 'request');
		$this->object_class = RaspHash::get($options, 'object_class');
	}

	/**
	 * Checks if connection exists, request is resource and no fetching was
	 * @return Boolean
	 */
	public function can_fetch() {
		return ($this->connection !== null) && ($this->connection !== false) && ($this->request !== false) && ($this->storage !== false);
	}

	/**
	 * Mock for rewind mehtods
	 */
	public function rewind() {
		/** Nothing do */
	}

	/**
	 * Return current fetching element
	 * @return Any
	 */
	public function current() {		
		return new $this->object_class($this->storage);
	}

	/**
	 * Set key to next
	 * @return Integer
	 */
	public function next() {
		return ++$this->position;
	}

	/**
	 * Return current key of current fetching element
	 * @return Integer
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Checks if can fetch and fetch one row complete
	 * @return Boolean
	 */
	public function valid() {
		return $this->can_fetch() && (false !== $this->fetch());
	}

	/**
	 * Static cinstructor
	 * @param Hash $options
	 * @return RaspActiveRecordCollection
	 */
	public static function initialize($options = array()) {
		return new RaspActiveRecordCollection($options);
	}

	/**
	 * Fetching row from resource and store it
	 * @return false || Array
	 */
	private function fetch() {
		return $this->storage = $this->connection->fetch($this->request);
	}

	/**
	 * Saves current fetched element to storage
	 */
	public function save() {
		$this->data[] = $this->current();
	}
}

?>
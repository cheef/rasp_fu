<?php

	require_once RASP_ORM_PATH . 'constructions/select.php';

  class RaspSQLConstructorException extends RaspException {};

  class RaspSQLConstructor {

    private static $types = array('select', 'insert', 'update', 'delete');

    public static function create($type, $options = array()){
      try {
        if(!in_array($type, self::$types)) throw new RaspSQLConstructorException();
				eval('$query_object = Rasp' . ucwords($type) . '::create();');
				return $query_object;
      } catch(RaspSQLConstructorException $e){ RaspCatcher::add($e); }
    }

  }

?>
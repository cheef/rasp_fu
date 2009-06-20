<?php

  rasp_lib(
    'orm.constructions.select',
    'exception', 'tools.catcher'
  );

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
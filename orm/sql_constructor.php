<?php

  class RaspSQLConstructorException extends RaspException {};

  class RaspSQLConstructor {

    private static $types = array('select', 'insert', 'update', 'delete');

    public static function create($type, $options = array()){
      try {
        if(in_array($type, self::$types)) throw new RaspSQLConstructorException();
      } catch(RaspSQLConstructorException $e){ RaspCatcher::add($e); }
    }

  }

?>
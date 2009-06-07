<?php

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

  class RaspElementaryException extends RaspException {};

  class RaspElementary {

    const EXCEPTION_NO_FOUND_MATCHES = "No found any variables in construct mask";
    
    private $construction = null, $variables = null, $sql = null, $values = null;

    private static $match_mask = '\[([a-zA-Z_0-9]+)\]';
    public static $values_closer = "'";
    public static $attributes_closer = "`";
    public static $values_delimiter = ",";

    public function __construct($attributes = array()){
      foreach($attributes as $attribute => $value) $this->$attribute = $value;
    }

    public static function create($attributes = array()){
      return new RaspElementary($attributes);
    }

    public function construction($mask){
      $this->construction = $mask;
      $this->variables = self::match($mask);
      return $this;
    }

    public function set($values, $delimiter = ',', $closer = ''){
      $this->values[] = $closer . $values . $closer;
      return $this;
    }

    public function make(){
      if(!empty($this->values)){
        $this->sql = $this->construction;
        foreach($this->variables as $key => $variable) $this->sql = str_replace('[' . $variable . ']', $this->values[$key], $this->sql);
      }
      return $this;
    }

    public function sql(){
      return $this->sql;
    }

    private static function match($mask){
      try {
        preg_match_all('|' . self::$match_mask . '|', $mask, $matches);
        if(empty($matches[0])) throw new RaspElementaryException(self::EXCEPTION_NO_FOUND_MATCHES);

        return RaspArray::second($matches);
      } catch(RaspElementaryException $e) { RaspCatcher::add($e); };
    }
  }

?>
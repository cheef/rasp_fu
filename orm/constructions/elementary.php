<?php

  rasp_lib(
    'types.array',
    'exception', 'tools.catcher'
  );

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

  class RaspElementaryException extends RaspException {};

  class RaspElementary {

    const EXCEPTION_NO_FOUND_MATCHES = "No found any variables in construct mask";
    const EXCEPTION_WRONG_SET_TYPE = "Wrong set type, expected string or integer";

    private $construction = null, $variables = null, $sql = null, $values = null, $type = null;

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

    public function construction($mask, $type = 'expression'){
      $this->construction = $mask;
      $this->type = $type;
      $this->variables = self::match($mask);
      return $this;
    }

    public function set($values){
    	try {
	    	if(is_string($values) || is_int($values)) $this->values[RaspArray::first($this->variables)][] = $values;
	    	else throw new RaspElementaryException(self::EXCEPTION_WRONG_SET_TYPE);
    	} catch (RaspSelectException $e) { RaspCatcher::add($e); }
      return $this;
    }

    public function make(){
      if(!empty($this->values))
        $this->sql = str_replace('[' . RaspArray::first($this->variables) . ']', $this->stringify_values(RaspArray::first($this->variables)), $this->construction);
      return $this;
    }

    private function stringify_values($attribute){
    	$attribute_values = RaspArray::index($this->values, $attribute, array());
    	return join($this->is_logic_type() ? ' AND ' : ', ', $attribute_values);
    }

    private function is_logic_type(){
    	return $this->type == 'logic';
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
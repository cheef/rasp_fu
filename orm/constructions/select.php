<?php

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_ORM_PATH . 'constructions/elementary.php';
  require_once RASP_ORM_PATH . 'constructions/expression.php';
  require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

  class RaspSelectException extends RaspException {};

  class RaspSelect {

    const EXCEPTION_WRONG_LIMIT_TYPE = 'Wrong limit type, expected integer';
    const EXCEPTION_WRONG_SELECT_PARAMS = 'Wrong params of select method, expected string of fields, option or array of fields';
    const EXCEPTION_WRONG_FROM_PARAMS = 'Wrong params of from method, expected string or array';
    const EXCEPTION_WRONG_WHERE_PARAMS = 'Wrong params of where method, expected string, array or RaspWhereExpression instance';
    const EXCEPTION_WRONG_ORDER_PARAMS = 'Wrong params of order method, expected string or array';

    private static $elements = array('select', 'from', 'where', 'order', 'limit', 'offset');

    public static $q = null;

    public function __construct(){
      $this->select = RaspElementary::create()->construction('SELECT [fields]');
      $this->from = RaspElementary::create()->construction('FROM [tables]');
      $this->where = RaspElementary::create()->construction('WHERE [conditions]');
      $this->order = RaspElementary::create()->construction('ORDER BY [fields]');
      $this->limit = RaspElementary::create()->construction('LIMIT [limit]');
      $this->offset = RaspElementary::create()->construction('OFFSET [offset]');
    }

    public static function q(){
      return self::$q = RaspWhereExpression::create();
    }

    public function select($fields = 'all'){
    	try {
	    	if(is_string($fields)){
	      	switch($fields){
	        	case '*': case 'all': $this->select->set('*'); break;
	        	default: $this->select->set($fields);
	      	}
	    	} elseif(is_array($fields)){
	    		foreach($fields as $key => $field) $fields[$key] =  RaspElementary::$attributes_closer . $field .  RaspElementary::$attributes_closer;
	    		$this->select->set(join(',', $fields));
	    	} else throw new RaspSelectException(self::EXCEPTION_WRONG_SELECT_PARAMS);
	      return $this;
    	} catch(RaspSelectException $e) { RaspCatcher::add($e); }
    }

    public function from($tables){
    	try {
	    	if(is_string($tables)) $this->from->set($tables);
	    	elseif(is_array($tables)) $this->from->set(join(',', $tables));
	    	else throw new RaspSelectException(self::EXCEPTION_WRONG_FROM_PARAMS);
	      return $this;
    	} catch(RaspSelectException $e) { RaspCatcher::add($e); }
    }

    public function where($conditions){
    	try {
	      if(is_string($conditions)) $this->where->set($conditions);
	      elseif(is_array($conditions)) $this->where->set(self::q()->andw($conditions)->sql());
	      elseif(RaspWhereExpression::is_expr($conditions))  $this->where->set($conditions->sql());
	      else throw new RaspSelectException(self::EXCEPTION_WRONG_WHERE_PARAMS);
	      return $this;
    	} catch(RaspSelectException $e) { RaspCatcher::add($e); }
    }

    public function order($ordering){
    	try {
	    	if(is_string($ordering)) $this->order->set($ordering);
	    	elseif(is_array($ordering)) foreach($ordering as $attribute_name => $dimension)
	    		$this->order->set(RaspElementary::$attributes_closer . $attribute_name . RaspElementary::$attributes_closer . ' ' . $dimension);
	    	else throw new RaspSelectException(self::EXCEPTION_WRONG_ORDER_PARAMS);
    	} catch(RaspSelectException $e) { RaspCatcher::add($e); }
      return $this;
    }

    public function group(){
    }

    public function limit($limit){
      try {
        if(!is_int($limit)) throw new RaspSelectException(self::EXCEPTION_WRONG_LIMIT_TYPE);
        $this->limit->set($limit);
      } catch(RaspSelectException $e) { RaspCatcher::add($e); }
      return $this;
    }

    public function offset(){

    }

    public function to_sql(){
      $sql = array();
      foreach(self::$elements as $element) $sql[] = $this->$element->make()->sql();
      return join(' ', RaspArray::compact($sql));
    }

    public static function create(){
      return new RaspSelect;
    }
  }

?>
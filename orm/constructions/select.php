<?php

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_ORM_PATH . 'constructions/elementary.php';
  require_once RASP_ORM_PATH . 'constructions/expression.php';
  require_once RASP_TOOLS_PATH . 'catcher.php';
	require_once RASP_PATH . 'exception.php';

  class RaspSelectException extends RaspException {};

  class RaspSelect {

    const EXCEPTION_WRONG_LIMIT_TYPE = 'Wrong limit type, limit must be a integer value';

    private static $elements = array('select', 'from', 'where', 'order', 'limit', 'offset');

    public static $q = null;

    public function __construct(){     
      $this->select = RaspElementary::create()->construction('SELECT [fields]');
      $this->from = RaspElementary::create()->construction('FROM [tables]');
      $this->where = RaspElementary::create()->construction('WHERE [conditions]');
      $this->order = RaspElementary::create()->construction('ORDER BY [field] [dimension]');
      $this->limit = RaspElementary::create()->construction('LIMIT [limit]');
      $this->offset = RaspElementary::create()->construction('OFFSET [offset]');
    }

    public static function q(){
      return self::$q = RaspWhereExpression::create();
    }

    public function select($fields = 'all'){
      switch($fields){
        case '*': case 'all': $this->select->set('*');
      }
      return $this;
    }

    public function from($tables){
      $this->from->set($tables);
      return $this;
    }

    public function where($conditions){
      if(is_string($conditions)) $this->where->set($conditions);
      elseif(is_array($conditions)) $this->where->set(self::q()->andw($conditions));
      return $this;
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

    public function order($order_array){
      $this->order = RaspArray::first($order_array);      
      return $this;
    }

    public function group(){      
    }
   
    public function to_sql(){
      $sql = array();
      foreach(self::$elements as $element) $sql[] = $this->$element->make()->sql();
      return join(' ', RaspArray::compact($sql));
    }

    public function create(){
      return new RaspSelect;
    }
  }

?>
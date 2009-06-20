<?php

  rasp_lib(
    'types.array',
    'orm.constructions.elementary', 'orm.constructions.expression', 'orm.constructions.interfaces.abstract_request',
    'exception', 'tools.catcher'
  );

  class RaspInsertException extends RaspException {};

  class RaspInsert extends RaspAbstractRequest {

		const EXCEPTION_WRONG_FIELDS_PARAMS = 'Wrong params of select method, expected string or array';
		const EXCEPTION_WRONG_WHERE_PARAMS = 'Wrong params of where method, expected string, array or RaspWhereExpression instance';
		const EXCEPTION_WRONG_VALUES_PARAMS = 'Wrong params of values method, expected string or array';

  	protected static $elements = array('insert', 'fields', 'values', 'where');

  	public function __construct(){
      $this->insert = RaspElementary::create()->construction('INSERT INTO [table_name]');
      $this->fields = RaspElementary::create()->construction('[fields]');
      $this->values = RaspElementary::create()->construction('[values]');
      $this->where = RaspElementary::create()->construction('WHERE [conditions]', 'logic');
    }

    public function insert($table_name){
    	if(empty($table_name)) return $this;
    	$this->insert->set($table_name);
    	return $this;
    }

    public function fields($fields){
    	if(empty($fields)) return $this;
    	try {
				if(is_string($fields)) $this->fields->set('(' . $fields . ')');
				elseif(is_array($fields)){
					foreach($fields as $key => $field) $fields[$key] =  RaspElementary::$attributes_closer . $field .  RaspElementary::$attributes_closer;
					$this->fields->set('(' . join(', ', $fields) . ')');
				} else throw new RaspInsertException(self::EXCEPTION_WRONG_FIELDS_PARAMS);
    	} catch(RaspInsertException $e) { RaspCatcher::add($e); }
    }

    public function values($values){
			if(empty($values)) return $this;
    	try {
				if(is_string($values)) $this->values->set('(' . $values . ')');
				elseif(is_array($values)){
					foreach($values as $key => $value) $values[$key] =  RaspElementary::$values_closer . $value .  RaspElementary::$values_closer;
					$this->values->set('(' . join(RaspElementary::$values_delimiter . ' ', $values) . ')');
				} else throw new RaspInsertException(self::EXCEPTION_WRONG_VALUES_PARAMS);
    	} catch(RaspInsertException $e) { RaspCatcher::add($e); }
    }

    public function where($conditions){
			if(empty($conditions)) return $this;
    	try {
	      if(is_string($conditions)) $this->where->set($conditions);
	      elseif(is_array($conditions)) $this->where->set(self::q()->andw($conditions)->sql());
	      elseif(RaspWhereExpression::is_expr($conditions))  $this->where->set($conditions->sql());
	      else throw new RaspInsertException(self::EXCEPTION_WRONG_WHERE_PARAMS);
	      return $this;
    	} catch(RaspInsertException $e) { RaspCatcher::add($e); }
    }

    public static function create(){
      return new RaspInsert;
    }
  }
?>
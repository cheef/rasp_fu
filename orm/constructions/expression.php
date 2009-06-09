<?php

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_ORM_PATH . 'constructions/elementary.php';

  class RaspWhereExpression {

    private $value, $type;

    private static $types = array('logic');

    public function eq($value){
      $this->value = is_null($value) ? ' IS NULL' : ' = ' . RaspElementary::$values_closer . $value . RaspElementary::$values_closer;
      return $this;
    }

    public function neq($value){
      $this->value =  is_null($value) ? ' IS NOT NULL' : ' != ' . RaspElementary::$values_closer . $value . RaspElementary::$values_closer;
      return $this;
    }

    public function orw($sql_array){
      $this->value = join(' OR ', $this->w($sql_array));
      return $this;
    }

    public function andw($sql_array){
      $this->value = join(' AND ', $this->w($sql_array));
      return $this;
    }

    public function sql(){
      return $this->value;
    }

    private function w($expressions_array){
    	$bind_values = array();
      foreach($expressions_array as $attribute => $expr_or_value)
      	$bind_values[] = '(' . (self::is_logic_expr($expr_or_value) ? $expr_or_value->sql() : $this->bind(array($attribute, $expr_or_value))) . ')';
      $this->type = 'logic';
      return $bind_values;
    }

    private function bind($bind_array){
      $value = RaspArray::second($bind_array);
      $value = self::is_expr($value) ? $value->sql() : $this->eq($value)->sql();
      return RaspElementary::$attributes_closer . RaspArray::first($bind_array) . RaspElementary::$attributes_closer . $value;
    }

    public static function is_expr($expr){
      return is_a($expr, 'RaspWhereExpression');
    }

    public static function is_logic_expr($expr){
    	return self::is_expr($expr) && $expr->type == 'logic';
    }

    public static function create(){
      return new RaspWhereExpression;
    }
  }

?>
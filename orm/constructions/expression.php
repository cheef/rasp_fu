<?php

  require_once RASP_TYPES_PATH . 'array.php';
  require_once RASP_ORM_PATH . 'constructions/elementary.php';

  class RaspWhereExpression {

    private $value;

    public function eq($value){
      $this->value = is_null($value) ? ' IS NULL' : ' = ' . RaspElementary::$values_closer . $value . RaspElementary::$values_closer;
      return $this;
    }

    public function neq($value){
      $this->value =  is_null($value) ? ' IS NOT NULL' : ' != ' . RaspElementary::$values_closer . $value . RaspElementary::$values_closer;
      return $this;
    }

    public function orw($sql_array){
      $binded = array();
      foreach($sql_array as $attribute => $value_expr) $binded[] = '(' . $this->bind(array($attribute, $value_expr)) . ')';
      return join(' OR ', $binded);
    }

    public function andw($sql_array){
      $binded = array();      
      foreach($sql_array as $attribute => $value_expr) $binded[] = '(' . $this->bind(array($attribute, $value_expr)) . ')';
      return join(' AND ', $binded);
    }

    private function bind($bind_array){
      $value = RaspArray::second($bind_array);      
      $value = self::is_expr($value) ? $value->sql() : $this->eq($value)->sql();
      return RaspElementary::$attributes_closer . RaspArray::first($bind_array) . RaspElementary::$attributes_closer . $value;
    }

    public function sql(){
      return $this->value;
    }

    public static function is_expr($expr){      
      return is_a($expr, 'RaspWhereExpression');
    }

    public static function create(){
      return new RaspWhereExpression;
    }

  }

?>
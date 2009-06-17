<?php

	require_once RASP_TYPES_PATH . 'array.php';
	require_once RASP_ORM_PATH . 'constructions/expression.php';

	abstract class RaspAbstractRequest {

		protected $elements = array();

		public static function q(){
      return RaspWhereExpression::create();
    }

    public function to_sql(){
      $sql = array();
      foreach($this->elements as $element) $sql[] = $this->$element->make()->sql();
      return join(' ', RaspArray::compact($sql));
    }

    abstract public static function create();
	}
?>
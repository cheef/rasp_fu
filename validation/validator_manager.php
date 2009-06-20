<?php

  rasp_lib(
    'validation.pattern_validator',
    'exception', 'tools.catcher'
  );

  class RaspValidatorManagerException extends RaspException {};

  class RaspValidatorManager {

    const EXCEPTION_WRONG_OPTION = 'Wrong option for validator manager';
    const EXCEPTION_WRONG_RULE = 'Wrong validation rule';

    protected static $available_options = array('patters', 'rules');
    protected $patterns = array(), $rules = array(), $messages = array();

    protected static $available_rules = array(
      'required' => array('message' => 'This field is required'),
      'number' => array('message' => 'Please enter a valid number'),
      'email' => array('message' => 'Please enter a valid email address'),
      'max' => array('message' => 'Please enter a value less than or equal to %max%'),
      'min' => array('message' => 'Please enter a value greater than or equal to %min%'),
      'credit_card' => array('message' => 'Please enter a valid credit card number')
    );

    public function __construct($options = array()){
      try {
        foreach($options as $option => $value)
          if(in_array($option, self::$available_options)) $this->$option = $value;
          else throw new RaspValidatorManagerException(self::EXCEPTION_WRONG_OPTION);
      } catch (RaspValidatorManagerException $e) { RaspCatcher::add($e); }
    }

    public function is_valid($value){
      try {
        foreach($this->rules as $rule_name => $rule_options) {
          if($this->is_without_options($rule_name)) $this->case_validator($rule_options, array(), $value);
          else $this->case_validator($rule_name, $rule_options, $value);
        }
        foreach($this->patterns as $pattern => $message) $this->initilize_validator_by_pattern($pattern, $message, $value);
        return empty($this->messages);
      } catch (RaspValidatorManagerException $e) { RaspCatcher::add($e); }
    }

    protected function case_validator($rule_name, $rule_options, $value){
      try {
        if(!in_array($rule_name, array_keys(self::$available_rules))) throw new RaspValidatorManagerException(self::EXCEPTION_WRONG_RULE);
        if(!is_array($rule_options))
          $options = array_merge(self::$available_rules[$rule_name], array($rule_name => $rule_options));
        else $options = array_merge(self::$available_rules[$rule_name], $rule_options);
        
        switch($rule_name){
          case 'required':
            if(empty($value)) {
              return $this->messages[] = $options['message'];
            } else return true;
          case 'number':
            if(!is_numeric($value)){
              return $this->messages[] = $options['message'];
            } else return true;
          case 'email':
            return true;
          case 'min':
            return true;
          case 'max':
            return true;
          case 'credit_card':
            return true;
        }
      } catch (RaspValidatorManagerException $e) { RaspCatcher::add($e); }
    }

    protected function initilize_validator_by_pattern($pattern, $message, $value){
      if(!RaspPatternValidator::initilize(array('pattern' => $pattern, 'value' => $value))->is_valid()){
        return $this->messages[] = $message;
      } else return true;
    }

    protected function is_without_options($rule_index){
      return is_int($rule_index);
    }

    public function messages(){
      return $this->messages;
    }

    public static function initilize($options = array()){
      return new RaspValidatorManager($options);
    }
  }
?>

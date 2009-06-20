<?php

  rasp_lib(
    'validation.interfaces.abstract_validator',
    'exception', 'tools.catcher'
  );

  class RaspPatternValidatorException extends RaspException {};
  
  class RaspPatternValidator extends RaspAbstractValidator {

    protected static $available_options = array('pattern', 'value');

    const EXCEPTION_WRONG_OPTION = 'Wrong option for validator';
    const EXCEPTION_MISSED_VALUE = 'Missing value to validate';
    const EXCEPTION_MISSED_PATTERN = 'Missing validation pattern';

    public function __construct($options){
      try {
        foreach($options as $option => $value)
          if(in_array($option, self::$available_options)) $this->$option = $value;
          else throw new RaspPatterValidatorException(self::EXCEPTION_WRONG_OPTION);
      } catch (RaspPatterValidatorException $e) { RaspCatcher::add($e); }
    }

    public function is_valid($value = null){
      try {
        if(!empty($value)) $this->value = $value;
        if(empty($this->value)) throw new RaspPatternValidatorException(self::EXCEPTION_MISSED_VALUE);
        if(empty($this->pattern)) throw new RaspPatternValidatorException(self::EXCEPTION_MISSED_PATTERN);
        preg_match($this->pattern, $this->value, $matches);
        return !empty($matches);
      } catch (RaspPatternValidatorException $e) { RaspCatcher::add($e); }
    }

    public static function initilize($options){
      return new RaspPatternValidator($options);
    }
  }
?>
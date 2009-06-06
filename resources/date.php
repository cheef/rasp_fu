<?php
	require_once RASP_RESOURCES_PATH . 'abstract_resource.php';

	class RaspDate extends RaspAbstractResource {

		public static function now($format = ''){
			return (empty($format) ? time() : date($format, time()));
		}

    public static function format($date, $format = ''){     
      if(empty($format)) return $date;
      else return date($format, strtotime($date));
    }

	}
?>
<?php
	require_once RASP_RESOURCES_PATH . 'abstract_resource.php';

	class RaspCSV extends RaspAbstractResource {

		public $delimiter = ';', $endloser = "'";

		public static function create($options){

		}
	}
?>
<?php

  rasp_lib(
    'tools.abstract_tool'
  );

	require_once RASP_VENDOR_PATH . 'minixml-1.3.8' . DIRECTORY_SEPARATOR . 'minixml.inc.php';

	class RaspXML extends RaspAbstractTool {

		public static function beautify($xml_source){
			$parser = new MiniXMLDoc();
			$parser->fromString($xml_source);
			return $parser->toString();
		}
	}
?>
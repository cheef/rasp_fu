<?php
	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_VENDOR_PATH . 'minixml-1.3.8' . DIRECTORY_SEPARATOR . 'minixml.inc.php';

	class RaspAmazonResponser extends RaspAbstractService {

		public static $has_errors = false, $errors = array();

		public static function has_errors($xml_source = null){
			if($xml_source){
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				$root =& $parser->getRoot();
				if($errors_node =& $root->getElement('Errors')){
					self::$has_errors = true;
					$error_node =& $errors_node->getAllChildren('Error');
					foreach($error_node as $error)
						$code =& $error->getElement('Code');
						$message =& $error->getElement('Message');
						self::$errors[] = array('code' => $code->getValue(), 'message' => $message->getValue());
					return true;
				}
			}
			return self::$has_errors;
		}

		public static function parse_seller_listing_search($xml_source){
			if(self::has_errors($xml_source)) return false;
			else {
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				$root = $parser->getRoot();
				$items = array();
				foreach($root->getElement('SellerListings')->getAllChildren('SellerListing') as $minixml_item){
					$item = array();
					foreach($minixml_item->getAllChildren() as $minixml_attribute){
						if($minixml_attribute->xnumChildren <= 1) $item[] = array($minixml_attribute->xname => $minixml_attribute->getValue());
						else foreach($minixml_attribute->getAllChildren() as $sub_attribute) $item[] = array($sub_attribute->xname, $sub_attribute->getValue());
					}
					$items[] = $item;
				}
			}
		}
	}
?>
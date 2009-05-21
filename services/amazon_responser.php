<?php
	require_once RASP_SERVICES_PATH . 'abstract_service.php';
	require_once RASP_VENDOR_PATH . 'minixml-1.3.8' . DIRECTORY_SEPARATOR . 'minixml.inc.php';

	class RaspAmazonResponser extends RaspAbstractService {

		public static $has_errors = false, $errors = array();

		public static function has_errors($xml_source = null){
			if($xml_source){
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				if($errors_node = $parser->getRoot()->getElement('Errors')){
					self::$has_errors = true;
					foreach($errors_node->getAllChildren('Error') as $error_node)
						self::$errors[] = array('code' => $error_node->getElement('Code')->getValue(), 'message' => $error_node->getElement('Message')->getValue());
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
				$items = array();
				foreach($parser->getRoot()->getElement('SellerListings')->getAllChildren('SellerListing') as $minixml_item){
					$item = array();
					foreach($minixml_item->getAllChildren() as $minixml_attribute){
						if($minixml_attribute->xnumChildren <= 1) $item[$minixml_attribute->xname] = $minixml_attribute->getValue();
						else foreach($minixml_attribute->getAllChildren() as $sub_attribute) $item[$sub_attribute->xname] = $sub_attribute->getValue();
					}
					$items[] = $item;
				}
				return $items;
			}
		}

		public static function parse_item_lookup_with_merchant($xml_source){
			if(self::has_errors($xml_source)) return false;
			else {
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				$item = array();
				foreach($parser->getRoot()->getElementByPath('Items/Item/OfferSummary')->getAllChildren() as $attribute_node){
					if($attribute_node->xnumChildren <= 1) $item[$attribute_node->xname] = $attribute_node->getValue();
					elseif($attribute_node->name() == 'LowestNewPrice') $item['LowestNewPrice'] = $attribute_node->getElement('Amount')->getValue();
					elseif($attribute_node->name() == 'LowestUsedPrice') $item['LowestUsedPrice'] = $attribute_node->getElement('Amount')->getValue();
					elseif($attribute_node->name() == 'LowestCollectiblePrice') $item['LowestCollectiblePrice'] = $attribute_node->getElement('Amount')->getValue();
				}
				if($offers = $parser->getRoot()->getElementByPath('Items/Item/Offers/Offer/OfferListing')){
					foreach($offers->getAllChildren() as $offer_node)
						if($offer_node->name() == 'Price') $item['Amount'] = $offer_node->getElement('Amount')->getValue();
				} else {
					self::$errors[] = array('code' => 'NO_ITEM', 'message' => 'Selected merchant don\'t sell this item');
					return false;
				}
				return $item;
			}
		}

		public static function parse_item_lookup($xml_source){
			if(self::has_errors($xml_source)) return false;
			else {
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				$item = array();
				foreach($parser->getRoot()->getElementByPath('Items/Item/OfferSummary')->getAllChildren() as $attribute_node){
					if($attribute_node->xnumChildren <= 1) $item[$attribute_node->xname] = $attribute_node->getValue();
					elseif($attribute_node->name() == 'LowestNewPrice') $item['LowestNewPrice'] = $attribute_node->getElement('Amount')->getValue();
					elseif($attribute_node->name() == 'LowestUsedPrice') $item['LowestUsedPrice'] = $attribute_node->getElement('Amount')->getValue();
					elseif($attribute_node->name() == 'LowestCollectiblePrice') $item['LowestCollectiblePrice'] = $attribute_node->getElement('Amount')->getValue();
				}
				return $item;
			}
		}

		public static function clear_errors(){
			self::$errors = array();
			self::$has_errors = false;
		}

		public static function parse_offers($xml_source){
			if(self::has_errors($xml_source)) return false;
			else {
				$parser = new MiniXMLDoc();
				$parser->fromString($xml_source);
				$item = array();
				$offers_html = $parser->getRoot()->getElementByPath('Items/Item/Offers');
				if(!empty($offers_html)){
					foreach($offers_html->getAllChildren() as $offers_node){
						if($offers_node->xnumChildren <= 1) $item[$offers_node->xname] = $offers_node->getValue();
						elseif($offers_node->xname == 'Offer') {
							$offer = array();
							foreach($offers_node->getElementByPath('OfferAttributes')->getAllChildren() as $offer_node) $offer[$offer_node->xname] = $offer_node->getValue();
							if($merchant_node = $offers_node->getElementByPath('Merchant')){
								foreach($merchant_node->getAllChildren() as $offer_node) $offer[$offer_node->xname] = $offer_node->getValue();
							} elseif($seller_node = $offers_node->getElementByPath('Seller')){
								foreach($seller_node->getAllChildren() as $offer_node) $offer[$offer_node->xname] = $offer_node->getValue();
							}
							foreach($offers_node->getElementByPath('OfferListing/Price')->getAllChildren() as $price_node) $offer[$price_node->xname] = $price_node->getValue();
							$item['Offers'][] = $offer;
						}
					}
				} else {
					self::$errors[] = array('code' => 'EMPTY_XML', 'message' => 'Empty response from amazon');
					return false;
				}
				return $item;
			}
		}
	}
?>
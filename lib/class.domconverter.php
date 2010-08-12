<?php

if(!function_exists("xmlentities")) {
	function xmlentities( $string ){
    	return str_replace(
			array ( '&', '"', "'", '<', '>' ),
			array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ),
			$string );
	}
}


class DOMConverter {
	
	static public function toXMLElement($dom) {
		
		if(is_a($dom, "DOMDocument")) {
			$dom = $dom->documentElement;
		}
		$rootXE = self::createXMLElement($dom);

		$nodes = $dom->childNodes;
		
		if ($nodes) {
			foreach($nodes as $node) {
				if($newNode = self::toXMLElement($node)) {
					$rootXE->appendChild($newNode);
				}					
			}
		}
		
		return $rootXE;
	}
	
	static function createXMLElement($node) {
		
		if(is_a($node, "DOMText")) {
			return;
		}
	
		// Get attributes into a proper array
		$items = array();
		if ($node->attributes) {
			foreach($node->attributes as $key => $value) {
				$items[$key] = xmlentities($node->getAttribute($key));
			}
		}		
		
		$value = "";
		
		// Just check to see if there are any child textnodes, and pull them out and set as value
		if ($node->childNodes) {
			foreach($node->childNodes as $childNode) {
				if(is_a($childNode, "DOMText") && trim($childNode->nodeValue) != '') {
					$value .= xmlentities($childNode->nodeValue);
				}
			}
		}		
	
		return new XMLElement($node->nodeName, $value, $items); 
	}
}
<?php
class Fu_XmlWriter {
    var $xml;
    function __construct() {
        $this->xml = new XmlWriter();
		$this->xml->openMemory();
    }

    function push ($element, $attributes = array()) {
        $this->xml->startElement($element);
        
		foreach ($attributes as $key => $value) {
            $this->xml->writeAttribute($key, $value);
        }
    }
    function element ($element, $content, $attributes = array()) {
        if (!$attributes) {
			$this->xml->writeElement($element, $content);
		}
		else {
			$this->xml->startElement($element);
			foreach ($attributes as $key => $value) {
				$this->xml->writeAttribute($key, $value);
			}
			
			$this->xml->writeCData($content);
			
			$this->xml->endElement();
		}
    }
    function pop () {
        $this->xml->endElement();
    }
    function getXml () {
        return $this->xml->flush(1);
    }
}

<?php
/**
 * MIT License (MIT)
 *
 * Copyright (c) 2014 Beno!t POLASZEK
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * SimpleXmlExtended
 * @author Beno!t POLASZEK - 2014
 */

namespace BenTools;

use DOMNode;
use SimpleXMLElement;

class SimpleXMLExtended extends SimpleXMLElement {

    /**
     * Constructor alias - useful for chaining
     * @return SimpleXMLExtended
     */
    public static function NewInstance() {
        $currentClass = new \ReflectionClass(get_called_class());
        return $currentClass->newInstanceArgs(func_get_args());
    }

    /**
     * String context - UTF8/ISO fix
     * Since SimpleXmlElement only works with UTF-8, you can experience issues if you're in ISO-8859-1.
     * If you have defined mb_internal_encoding earlier, we'll try to properly convert the string.
     * This only works on PHP 5.4+.
     *
     * @return string
     */
    public function __toString() {
        return mb_convert_encoding($this->{0}, mb_http_output(), mb_detect_encoding($this->{0}));
    }

    /**
     * Get node name
     *
     * @return string
     */
    public function getName() {
        return dom_import_simplexml($this)->nodeName;
    }

    /**
     * Get node type
     *
     * @return int
     */
    public function getType() {
        return dom_import_simplexml($this)->nodeType;
    }

    /**
     * Returns the first element of an xpath query
     *
     * @param $path
     * @return self|bool
     */
    public function dXpath($path) {
        $xpath = $this->xpath($path);
        return (is_array($xpath)) ? current($xpath) : $xpath;
    }

    /**
     * Gets the parent element
     *
     * @return self|bool
     */
    public function getParent() {
        return $this->dXpath('..');
    }

    /**
     * Adds a child to the current element and return it in the same way as original
     * simpleXMLElement but without the original value verification that sometimes is just a mess.
     *
     * @param string $nodeName
     * @param string $value
     * @param string $nameSpace
     * @return self - sub-node
     */
    public function addChild($nodeName, $value = null, $nameSpace = null) {
        $node = parent::addChild($nodeName, null, $nameSpace);
        if ($value !== null) {
            $domNode    =   dom_import_simplexml($node);
            $doc        =   $domNode->ownerDocument;
            $domNode    ->  appendChild($doc->createTextNode($value));
        }
        return $node;
    }

    /**
     * Same as addChild method but the value will be put inside a CData section.
     *
     * @param string $nodeName
     * @param string $value
     * @param string $nameSpace
     * @return self - sub-node
     */
    public function addCDataChild($nodeName, $value, $nameSpace = null) {
        $node = $this->addChild($nodeName, null, $nameSpace);
        return $node->addCData($value);
    }

    /**
     * Adds a CData section to the current element and return this for method chaining
     *
     * @param string $cdata
     * @return  - Provides fluent interface
     */
    public function addCData($cdata) {
        $node = dom_import_simplexml($this);
        $doc = $node->ownerDocument;
        $node->appendChild($doc->createCDATASection($cdata));
        return $this;
    }

    /**
     * Exactly the same as simpleXMLElement::addAttribute()
     * but returns $this for method chaining
     *
     * @param string $name
     * @param null   $value
     * @param null   $ns
     * @return $this - Provides fluent interface
     */
    public function addAttribute($name, $value = null, $ns = null) {
        parent::addAttribute($name, $value, $ns);
        return $this;
    }

    /**
     * Appends a SimpleXmlExtended element
     *
     * @param SimpleXmlElement $child
     * @return $this - Provides fluent interface
     */
    public function appendChild(SimpleXMLElement &$child) {
        $domNodes   =   self::getSameDocDomNodes($this, $child);
        $node       =   $domNodes[0];
        $_child     =   $domNodes[1];
        $node       ->  appendChild($_child);
        $child      =   simplexml_import_dom($_child);
        return $this;
    }

    /**
     * Removes given SimpleXmlElement from current element
     *
     * @param SimpleXMLElement $child
     * @return $this
     */
    public function removeChild(SimpleXMLElement $child) {
        $node   =   dom_import_simplexml($this);
        $child  =   dom_import_simplexml($child);
        $node   ->  removeChild($child);
        return $this;
    }

    /**
     * Replaces a child [very]SimpleXmlElement with another [very]SimpleXmlElement
     *
     * @param SimpleXmlElement $newChild passed by reference
     *                                   (must be done if we want further modification to the newChild element to be
     *                                   applyed to the document)
     * @param SimpleXmlElement $oldChild
     * @return $this
     */
    public function replaceChild(SimpleXmlElement &$newChild, SimpleXmlElement $oldChild) {
        list($oldChild, $_newChild) = self::getSameDocDomNodes($oldChild, $newChild);
        $oldChild   ->  parentNode->replaceChild($_newChild, $oldChild);
        $newChild   =   simplexml_import_dom($_newChild);
        return $this;
    }

    /**
     * Removes the current Xml Element from its parent
     *
     * @return $this - Provides fluent interface
     */
    public function remove() {
        $node   =   dom_import_simplexml($this);
        $node   ->  parentNode->removeChild($node);
        return $this;
    }

    /**
     * Replaces current element with another SimpleXmlElement
     *
     * @param SimpleXmlElement $replaceElement
     * @return $this
     */
    public function replace(SimpleXmlElement &$replaceElement) {
        list($node, $_replaceElmt) = self::getSameDocDomNodes($this, $replaceElement);
        $node->parentNode   ->  replaceChild($_replaceElmt, $node);
        $replaceElement     =   simplexml_import_dom($_replaceElmt);
        return $this;
    }

    /**
     * Static utility method to get two dom elements and ensure that the second is part of the same document than the
     * first given.
     *
     * @param SimpleXmlElement $node1
     * @param SimpleXmlElement $node2
     * @return DOMNode[]
     */
    static public function getSameDocDomNodes(SimpleXMLElement $node1, SimpleXMLElement $node2) {
        $node1 = dom_import_simplexml($node1);
        $node2 = dom_import_simplexml($node2);
        if (!$node1->ownerDocument->isSameNode($node2->ownerDocument))
            $node2 = $node1->ownerDocument->importNode($node2, true);
        return [$node1, $node2];
    }

    /**
     * Tries to automatically create a child from an array
     * Since it implies some magic, use it at your own risk
     * @param array $array
     * @return $this - Provides fluent interface
     */
    public function addChildFromArray(array $array = []) {

            foreach ($array AS $key => $value) {

                # An XML node can't be numeric : if so, let's take the current node name
                if (is_numeric($key))
                    $_key = $this->getName();

                # If it's an associative array, take the key as the node name
                else
                    $_key = $key;

                # Numeric values
                if (is_numeric($value))
                    $this->addChild($_key, $value);

                # Boolean values => change them to strings
                elseif (is_bool($value))
                    $this->addChild($_key, (($value) ? 'true' : 'false'));

                # String values (or objects castable as strings)
                elseif (is_string($value) || (is_object($value) && in_array('__toString', get_class_methods($value))))
                    $this->addCdataChild($_key, (string) $value);

                # Array values (or objects not castable as strings => casted as arrays)
                elseif (is_array($value) || (is_object($value) && !in_array('__toString', get_class_methods($value)) && $value = (array) $value))
                    $this->addChild($_key)->addChildFromArray($value);

            }

        return $this;
    }

    /**
     * Magically transforms a multidimensionnal array to an Xml tree.
     * @param array            $array
     * @param SimpleXMLElement $simpleXMLElement
     * @return self
     */
    public static function ArrayToXml(array $array, SimpleXMLElement $simpleXMLElement = null) {
        if (is_null($simpleXMLElement))
            $simpleXMLElement = new static("<Array></Array>");
        return $simpleXMLElement->addChildFromArray($array);
    }

}
	
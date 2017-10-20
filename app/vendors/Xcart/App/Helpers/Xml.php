<?php

namespace Xcart\App\Helpers;

use DOMDocument;
use Exception;

/**
 * Class Xml
 * @package Mindy\Helper
 */
class Xml
{
    /**
     * Convert an Array to XML
     * @param string $rootNode
     * @param array $arr - aray to be converterd
     * @param bool $formatOutput
     * @param string $version
     * @param string $encoding
     * @return string
     */
    public static function encode($rootNode = 'response', array $arr = [], $formatOutput = false, $version = '1.0', $encoding = 'UTF-8')
    {
        $xml = new DomDocument($version, $encoding);
        $xml->appendChild(self::convert($xml, $rootNode, $arr));
        $xml->formatOutput = $formatOutput;
        return $xml->saveXML();
    }

    /**
     * Convert an Array to XML
     * @param $xml DOMDocument
     * @param string $rootNode - name of the root node to be converted
     * @param array $data - array to be converted
     * @throws Exception
     * @return \DOMNode
     */
    private static function convert($xml, $rootNode, $data)
    {
        $node = $xml->createElement($rootNode);

        if (is_array($data)) {
            // get the attributes first.;
            if (isset($data['@attributes'])) {
                foreach ($data['@attributes'] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $rootNode);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($data['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($data['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($data['@value'])));
                unset($data['@value']); //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if (isset($data['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($data['@cdata'])));
                unset($data['@cdata']); //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($data)) {
            // recurse to get the node for that key
            foreach ($data as $key => $value) {
                if(strpos($key, '@') === 0 && is_array($value)) {
                    $newKey = str_replace('@', '', $key);
                    foreach($value as $v) {
                        $node->appendChild(self::convert($xml, $newKey, $v));
                    }
                } else {
                    if (is_array($value) && is_numeric($key)) {
                        // MORE THAN ONE NODE OF ITS KIND;
                        // if the new array is numeric index, means it is array of nodes of the same kind
                        // it should follow the parent key name
                        foreach ($value as $k => $v) {
                            if (!self::isValidTagName($k)) {
                                throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $k . ' in node: ' . $rootNode);
                            }

                            $node->appendChild(self::convert($xml, $k, $v));
                        }
                    } else {
                        if (!self::isValidTagName($key)) {
                            throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $rootNode);
                        }

                        // ONLY ONE NODE OF ITS KIND
                        $node->appendChild(self::convert($xml, $key, $value));
                    }
                    unset($data[$key]); //remove the key from the array once done.
                }
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($data)) {
            $node->appendChild($xml->createTextNode(self::bool2str($data)));
        }

        return $node;
    }

    /**
     * Get string representation of boolean value
     * @param $v
     * @return string
     */
    private static function bool2str($v)
    {
        if ($v === true) {
            return 'true';
        } else if ($v === false) {
            return 'false';
        }

        return $v;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($key)
    {
        preg_match('/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i', $key, $matches);
        return isset($matches[0]) && $matches[0] == $key;
    }
}

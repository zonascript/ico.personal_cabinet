<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 14/04/16 07:17
 */

namespace Xcart\App\Helpers;


class Text
{
    /**
     * Converts camel case string to underscore string.
     * Examples:
     *
     * 'simpleTest' => 'simple_test'
     * 'easy' => 'easy'
     * 'HTML' => 'html'
     * 'simpleXML' => 'simple_xml'
     * 'PDFLoad' => 'pdf_load'
     *
     * @param $input string
     * @return string
     */
    public static function camelCaseToUnderscores($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }

    public static function removePrefix($prefix, $text)
    {
        if (0 === mb_strpos($text, $prefix, null, 'UTF-8')) {
            $text = (string) mb_substr($text, strlen($prefix), null, 'UTF-8');
        }
        return $text;
    }

    public static function startsWith($haystack, $needle)
    {
        $length = mb_strlen($needle, 'UTF-8');
        return (mb_substr($haystack, 0, $length, 'UTF-8') === $needle);
    }

    public static function endsWith($haystack, $needle)
    {
        $length = mb_strlen($needle, 'UTF-8');
        if ($length == 0) {
            return true;
        }
        return (mb_substr($haystack, -$length, null, 'UTF-8') === $needle);
    }
}
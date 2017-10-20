<?php

namespace Xcart\App\Translate;

/**
 * Class Translate DUMMY
 *
 * @package Xcart\App\Translate
 */
class Translate
{
    private static $_self;

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (!static::$_self) {
            static::$_self = new static;
        }

        return static::$_self;
    }

    public function t($dict, $text, $params = [])
    {
        return $this->stringReplacement($text, $params);
    }


    public function stringReplacement($str, array $params = [])
    {
        if ($params) {
            $str = strtr($str, $params);
//            foreach ($params as $k=>$v) {
//                $str = str_replace($k, $str, $v);
//            }
        }

        return $str;
    }

}
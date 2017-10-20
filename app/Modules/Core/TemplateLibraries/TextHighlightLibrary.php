<?php
namespace Modules\Core\TemplateLibraries;


use Xcart\App\Template\TemplateLibrary;

class TextHighlightLibrary extends TemplateLibrary
{
    private static $tag_prebuild = [];

    /**
     * @name text_highlight
     * @kind modifier
     * @return string
     */
    public static function textHighlight($str, $search, $tag = 'em')
    {
        list($result) = self::coreHighlight($str, $search, $tag);

        return $result;
    }


    /**
     * @name words_highlight
     * @kind modifier
     * @return string
     * @throws \Exception
     */
    public static function wordsHighlight($str, $search, $tag = 'em')
    {
        $words = [];

        if (is_string($search)) {
            $words = preg_split('/[\s+\n\r\t]/', $search);
        }
        
        if (is_array($search)) {
            $words = $search;
        }

        if (empty($words) || !is_array($words)) {
            throw new \Exception('search words in not array');
        }

        list($str, $founded) = self::coreHighlight($str, implode(' ', $words), $tag);

        if (!$founded) {
            foreach ($words as $s) {
                list($str) = self::coreHighlight($str, $s, $tag);
            }
        }

        return $str;
    }

    private static function coreHighlight($str, $search, $tag = 'em')
    {
        if ($founded = self::searchSubstring($str, trim($search)))
        {
            $len = strlen($founded);
            $pos = strpos(strtolower($str), strtolower($founded));

            $founded = str_replace('{content}', substr($str, $pos, $len), self::parseTag($tag));

            $str1 = substr($str, 0, $pos);
            $str2 = substr($str, $pos + $len);

            if (!empty($str2)) {
                $str2 = self::textHighlight($str2, $search, $tag);
            }

            return [$str1 . $founded . $str2, true];
        }

        return [$str, false];
    }

    private static function searchSubstring($str, $search)
    {
        if ($str && $search  && strlen($str) >= 3 && strlen($search) >= 3) {

            if (strpos(strtolower($str), strtolower($search)) === false) {
                return self::searchSubstring($str, substr($search, 0, -1));
            }

            return $search;
        }

        return false;
    }

    private static function parseTag($str)
    {
        if (!empty(self::$tag_prebuild[$str])) {
            return self::$tag_prebuild;
        }

        $id = '';
        $property = [];
        $classes = [];

        preg_match_all('/\[([\w-]+(=(.*?)|))\]/', $str, $t_property);
        if (!empty($t_property[1])) {
            $property = $t_property[1];

            $str = preg_replace('/(\[([\w-]+(=(.*?)|))\])/', '', $str);
        }

        preg_match_all('/\.([\w-]+)/', $str, $t_classes);
        if (!empty($t_classes[1])) {
            $classes = $t_classes[1];

            $str = preg_replace('/(\.([\w-]+))/', '', $str);
        }

        preg_match('/\#([\w-]+)/', $str, $t_id);
        if (!empty($t_id[1])) {
            $id = $t_id[1];

            $str = preg_replace('/(\#([\w-]+))/', '', $str);
        }

        $tag = "<{$str}";
        if ($id) {
            $tag .= " id='{$id}'";
        }
        if ($classes) {
            $tag .= " class='".implode(' ', $classes)."'";
        }
        if ($property) {
            $tag .= implode(' ', $property);
        }
        $tag .= ">{content}</{$str}>";

        self::$tag_prebuild[$str] = $tag;
        return $tag;
    }
}
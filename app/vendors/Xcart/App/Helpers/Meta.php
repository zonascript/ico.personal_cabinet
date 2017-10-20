<?php

namespace Xcart\App\Helpers;

/**
 * Class Meta
 * @package Xcart\App\Helpers
 */
class Meta
{
    /**
     * @var int
     */
    public static $description_length = 30;
    /**
     * @var int
     */
    public static $keywords_max_length = 3;
    /**
     * @var int
     */
    public static $keywords_count = 10;
    /**
     * @var string default encoding
     */
    public static $encoding = 'UTF-8';

    /**
     * @param $string
     * @param null $separator
     * @param bool $toLowCase
     * @return mixed|string
     */
    public static function cleanSeparators($string, $separator = null, $toLowCase = true)
    {
        $output = $string;

        // Clean duplicate or trailing separators.
        if (isset($separator) && mb_strlen($separator, self::$encoding)) {
            // Escape the separator.
            $seppattern = preg_quote($separator, '/');

            // Trim any leading or trailing separators.
            $output = preg_replace("/^$seppattern+|$seppattern+$/", '', $output);

            // Replace trailing separators around slashes.
            $output = preg_replace("/$seppattern+\/|\/$seppattern+/", "/", $output);

            // Replace multiple separators with a single one.
            $output = preg_replace("/$seppattern+/", $separator, $output);
        }

        // Optionally convert to lower case.
        if ($toLowCase) {
            $output = mb_strtolower($output, self::$encoding);
        }

        return $output;
    }

    public static function cleanString($string, $separator = '-', $cleanPunctuation = true, $cleanSlash = true)
    {
        if ($cleanPunctuation) {
            $string = str_replace(self::$punctuations, $separator, $string);
        }

        // If something is already urlsafe then don't remove slashes
        if ($cleanSlash) {
            $string = str_replace('/', $separator, $string);
        }

        $string = strtr($string, self::$dictArray);

        // Always replace whitespace with the separator.
        $string = preg_replace('/\s+/', $separator, $string);

        // Trim duplicates and remove trailing and leading separators.
        $value = self::cleanSeparators($string, $separator);
        return $value;
    }

    public static function generateKeywords($text)
    {
        $wordsArray = self::keywordsExplodeStr($text);
        $resultArray = self::keywordsCount($wordsArray);

        $str = "";
        $i = 0;
        foreach ($resultArray as $key => $val) {
            $str .= $key . ", ";
            $i++;
            if ($i == self::$keywords_count) {
                break;
            }
        }

        return trim(mb_substr($str, 0, mb_strlen($str, self::$encoding) - 2, self::$encoding));
    }

    protected static function keywordsExplodeStr($text)
    {
        $text = preg_replace("( +)", " ", self::clearText($text));
        return explode(" ", trim($text));
    }

    public static function clearText($text, $keywords = true)
    {
        $search = [
            "'&\w+;'i", // Удаление тегов
            "/\s+/", // Удаление двойных пробелов и табуляций
            "/\d+/", // Удаление двойных пробелов и табуляций
        ];

        $replace = [" ", " ", ""];

        $search[] = ($keywords) ? "/[^A-ZА-Я0-9]+/ui" : "/[^A-ZА-Я0-9,.;!?]+/ui";
        $replace[] = ($keywords) ? " " : " ";

        $text = strip_tags($text);
        $out = preg_replace($search, $replace, $text);
        return $out ? $out : $text;
    }

    protected static function keywordsCount($wordsArray)
    {
        $tmp = [];

        foreach ($wordsArray as $item) {
            if (mb_strlen($item, self::$encoding) >= self::$keywords_max_length) {
                $item = mb_strtolower($item, self::$encoding);
                if (array_key_exists($item, $wordsArray)) {
                    $tmp[$item]++;
                } else {
                    $tmp[$item] = 1;
                }
            }
        }

        arsort($tmp);
        return $tmp;
    }

    public static function generateDescription($text)
    {
        return self::descriptionLimit(self::clearText($text, false));
    }

    protected static function descriptionLimit($text, $sep = ' ')
    {
        $words = explode(' ', $text);
        if (count($words) > self::$description_length) {
            $text = join($sep, array_slice($words, 0, self::$description_length));
        }

        return (mb_strlen($text, self::$encoding) > 200) ? mb_substr($text, 0, 200, self::$encoding) : $text;
    }

    public static $dictArray = [
        "À" => "A",
        "Á" => "A",
        "Â" => "A",
        "Ã" => "A",
        "Ä" => "Ae",
        "Å" => "A",
        "Æ" => "A",
        "Ā" => "A",
        "Ą" => "A",
        "Ă" => "A",
        "Ç" => "C",
        "Ć" => "C",
        "Č" => "C",
        "Ĉ" => "C",
        "Ċ" => "C",
        "Ď" => "D",
        "Đ" => "D",
        "È" => "E",
        "É" => "E",
        "Ê" => "E",
        "Ë" => "E",
        "Ē" => "E",
        "Ę" => "E",
        "Ě" => "E",
        "Ĕ" => "E",
        "Ė" => "E",
        "Ĝ" => "G",
        "Ğ" => "G",
        "Ġ" => "G",
        "Ģ" => "G",
        "Ĥ" => "H",
        "Ħ" => "H",
        "Ì" => "I",
        "Í" => "I",
        "Î" => "I",
        "Ï" => "I",
        "Ī" => "I",
        "Ĩ" => "I",
        "Ĭ" => "I",
        "Į" => "I",
        "İ" => "I",
        "Ĳ" => "IJ",
        "Ĵ" => "J",
        "Ķ" => "K",
        "Ľ" => "K",
        "Ĺ" => "K",
        "Ļ" => "K",
        "Ŀ" => "K",
        "Ł" => "L",
        "Ñ" => "N",
        "Ń" => "N",
        "Ň" => "N",
        "Ņ" => "N",
        "Ŋ" => "N",
        "Ò" => "O",
        "Ó" => "O",
        "Ô" => "O",
        "Õ" => "O",
        "Ö" => "Oe",
        "Ø" => "O",
        "Ō" => "O",
        "Ő" => "O",
        "Ŏ" => "O",
        "Œ" => "OE",
        "Ŕ" => "R",
        "Ř" => "R",
        "Ŗ" => "R",
        "Ś" => "S",
        "Ş" => "S",
        "Ŝ" => "S",
        "Ș" => "S",
        "Š" => "S",
        "Ť" => "T",
        "Ţ" => "T",
        "Ŧ" => "T",
        "Ț" => "T",
        "Ù" => "U",
        "Ú" => "U",
        "Û" => "U",
        "Ü" => "Ue",
        "Ū" => "U",
        "Ů" => "U",
        "Ű" => "U",
        "Ŭ" => "U",
        "Ũ" => "U",
        "Ų" => "U",
        "Ŵ" => "W",
        "Ŷ" => "Y",
        "Ÿ" => "Y",
        "Ý" => "Y",
        "Ź" => "Z",
        "Ż" => "Z",
        "Ž" => "Z",
        "à" => "a",
        "á" => "a",
        "â" => "a",
        "ã" => "a",
        "ä" => "ae",
        "ā" => "a",
        "ą" => "a",
        "ă" => "a",
        "å" => "a",
        "æ" => "ae",
        "ç" => "c",
        "ć" => "c",
        "č" => "c",
        "ĉ" => "c",
        "ċ" => "c",
        "ď" => "d",
        "đ" => "d",
        "è" => "e",
        "é" => "e",
        "ê" => "e",
        "ë" => "e",
        "ē" => "e",
        "ę" => "e",
        "ě" => "e",
        "ĕ" => "e",
        "ė" => "e",
        "ƒ" => "f",
        "ĝ" => "g",
        "ğ" => "g",
        "ġ" => "g",
        "ģ" => "g",
        "ĥ" => "h",
        "ħ" => "h",
        "ì" => "i",
        "í" => "i",
        "î" => "i",
        "ï" => "i",
        "ī" => "i",
        "ĩ" => "i",
        "ĭ" => "i",
        "į" => "i",
        "ı" => "i",
        "ĳ" => "ij",
        "ĵ" => "j",
        "ķ" => "k",
        "ĸ" => "k",
        "ł" => "l",
        "ľ" => "l",
        "ĺ" => "l",
        "ļ" => "l",
        "ŀ" => "l",
        "ñ" => "n",
        "ń" => "n",
        "ň" => "n",
        "ņ" => "n",
        "ŉ" => "n",
        "ŋ" => "n",
        "ò" => "o",
        "ó" => "o",
        "ô" => "o",
        "õ" => "o",
        "ö" => "oe",
        "ø" => "o",
        "ō" => "o",
        "ő" => "o",
        "ŏ" => "o",
        "œ" => "oe",
        "ŕ" => "r",
        "ř" => "r",
        "ŗ" => "r",
        "ś" => "s",
        "š" => "s",
        "ş" => "s",
        "ť" => "t",
        "ţ" => "t",
        "ù" => "u",
        "ú" => "u",
        "û" => "u",
        "ü" => "ue",
        "ū" => "u",
        "ů" => "u",
        "ű" => "u",
        "ŭ" => "u",
        "ũ" => "u",
        "ų" => "u",
        "ŵ" => "w",
        "ÿ" => "y",
        "ý" => "y",
        "ŷ" => "y",
        "ż" => "z",
        "ź" => "z",
        "ž" => "z",
        "ß" => "ss",
        "ſ" => "ss",
        "Α" => "A",
        "Ά" => "A",
        "Ἀ" => "A",
        "Ἁ" => "A",
        "Ἂ" => "A",
        "Ἃ" => "A",
        "Ἄ" => "A",
        "Ἅ" => "A",
        "Ἆ" => "A",
        "Ἇ" => "A",
        "ᾈ" => "A",
        "ᾉ" => "A",
        "ᾊ" => "A",
        "ᾋ" => "A",
        "ᾌ" => "A",
        "ᾍ" => "A",
        "ᾎ" => "A",
        "ᾏ" => "A",
        "Ᾰ" => "A",
        "Ᾱ" => "A",
        "Ὰ" => "A",
        "ᾼ" => "A",
        "Β" => "B",
        "Γ" => "G",
        "Δ" => "D",
        "Ε" => "E",
        "Έ" => "E",
        "Ἐ" => "E",
        "Ἑ" => "E",
        "Ἒ" => "E",
        "Ἓ" => "E",
        "Ἔ" => "E",
        "Ἕ" => "E",
        "Ὲ" => "E",
        "Ζ" => "Z",
        "Η" => "I",
        "Ή" => "I",
        "Ἠ" => "I",
        "Ἡ" => "I",
        "Ἢ" => "I",
        "Ἣ" => "I",
        "Ἤ" => "I",
        "Ἥ" => "I",
        "Ἦ" => "I",
        "Ἧ" => "I",
        "ᾘ" => "I",
        "ᾙ" => "I",
        "ᾚ" => "I",
        "ᾛ" => "I",
        "ᾜ" => "I",
        "ᾝ" => "I",
        "ᾞ" => "I",
        "ᾟ" => "I",
        "Ὴ" => "I",
        "ῌ" => "I",
        "Θ" => "TH",
        "Ι" => "I",
        "Ί" => "I",
        "Ϊ" => "I",
        "Ἰ" => "I",
        "Ἱ" => "I",
        "Ἲ" => "I",
        "Ἳ" => "I",
        "Ἴ" => "I",
        "Ἵ" => "I",
        "Ἶ" => "I",
        "Ἷ" => "I",
        "Ῐ" => "I",
        "Ῑ" => "I",
        "Ὶ" => "I",
        "Κ" => "K",
        "Λ" => "L",
        "Μ" => "M",
        "Ν" => "N",
        "Ξ" => "KS",
        "Ο" => "O",
        "Ό" => "O",
        "Ὀ" => "O",
        "Ὁ" => "O",
        "Ὂ" => "O",
        "Ὃ" => "O",
        "Ὄ" => "O",
        "Ὅ" => "O",
        "Ὸ" => "O",
        "Π" => "P",
        "Ρ" => "R",
        "Ῥ" => "R",
        "Σ" => "S",
        "Τ" => "T",
        "Υ" => "Y",
        "Ύ" => "Y",
        "Ϋ" => "Y",
        "Ὑ" => "Y",
        "Ὓ" => "Y",
        "Ὕ" => "Y",
        "Ὗ" => "Y",
        "Ῠ" => "Y",
        "Ῡ" => "Y",
        "Ὺ" => "Y",
        "Φ" => "F",
        "Χ" => "X",
        "Ψ" => "PS",
        "Ω" => "O",
        "Ώ" => "O",
        "Ὠ" => "O",
        "Ὡ" => "O",
        "Ὢ" => "O",
        "Ὣ" => "O",
        "Ὤ" => "O",
        "Ὥ" => "O",
        "Ὦ" => "O",
        "Ὧ" => "O",
        "ᾨ" => "O",
        "ᾩ" => "O",
        "ᾪ" => "O",
        "ᾫ" => "O",
        "ᾬ" => "O",
        "ᾭ" => "O",
        "ᾮ" => "O",
        "ᾯ" => "O",
        "Ὼ" => "O",
        "ῼ" => "O",
        "α" => "a",
        "ά" => "a",
        "ἀ" => "a",
        "ἁ" => "a",
        "ἂ" => "a",
        "ἃ" => "a",
        "ἄ" => "a",
        "ἅ" => "a",
        "ἆ" => "a",
        "ἇ" => "a",
        "ᾀ" => "a",
        "ᾁ" => "a",
        "ᾂ" => "a",
        "ᾃ" => "a",
        "ᾄ" => "a",
        "ᾅ" => "a",
        "ᾆ" => "a",
        "ᾇ" => "a",
        "ὰ" => "a",
        "ᾰ" => "a",
        "ᾱ" => "a",
        "ᾲ" => "a",
        "ᾳ" => "a",
        "ᾴ" => "a",
        "ᾶ" => "a",
        "ᾷ" => "a",
        "β" => "b",
        "γ" => "g",
        "δ" => "d",
        "ε" => "e",
        "έ" => "e",
        "ἐ" => "e",
        "ἑ" => "e",
        "ἒ" => "e",
        "ἓ" => "e",
        "ἔ" => "e",
        "ἕ" => "e",
        "ὲ" => "e",
        "ζ" => "z",
        "η" => "i",
        "ή" => "i",
        "ἠ" => "i",
        "ἡ" => "i",
        "ἢ" => "i",
        "ἣ" => "i",
        "ἤ" => "i",
        "ἥ" => "i",
        "ἦ" => "i",
        "ἧ" => "i",
        "ᾐ" => "i",
        "ᾑ" => "i",
        "ᾒ" => "i",
        "ᾓ" => "i",
        "ᾔ" => "i",
        "ᾕ" => "i",
        "ᾖ" => "i",
        "ᾗ" => "i",
        "ὴ" => "i",
        "ῂ" => "i",
        "ῃ" => "i",
        "ῄ" => "i",
        "ῆ" => "i",
        "ῇ" => "i",
        "θ" => "th",
        "ι" => "i",
        "ί" => "i",
        "ϊ" => "i",
        "ΐ" => "i",
        "ἰ" => "i",
        "ἱ" => "i",
        "ἲ" => "i",
        "ἳ" => "i",
        "ἴ" => "i",
        "ἵ" => "i",
        "ἶ" => "i",
        "ἷ" => "i",
        "ὶ" => "i",
        "ῐ" => "i",
        "ῑ" => "i",
        "ῒ" => "i",
        "ῖ" => "i",
        "ῗ" => "i",
        "κ" => "k",
        "λ" => "l",
        "μ" => "m",
        "ν" => "n",
        "ξ" => "ks",
        "ο" => "o",
        "ό" => "o",
        "ὀ" => "o",
        "ὁ" => "o",
        "ὂ" => "o",
        "ὃ" => "o",
        "ὄ" => "o",
        "ὅ" => "o",
        "ὸ" => "o",
        "π" => "p",
        "ρ" => "r",
        "ῤ" => "r",
        "ῥ" => "r",
        "σ" => "s",
        "ς" => "s",
        "τ" => "t",
        "υ" => "y",
        "ύ" => "y",
        "ϋ" => "y",
        "ΰ" => "y",
        "ὐ" => "y",
        "ὑ" => "y",
        "ὒ" => "y",
        "ὓ" => "y",
        "ὔ" => "y",
        "ὕ" => "y",
        "ὖ" => "y",
        "ὗ" => "y",
        "ὺ" => "y",
        "ῠ" => "y",
        "ῡ" => "y",
        "ῢ" => "y",
        "ῦ" => "y",
        "ῧ" => "y",
        "φ" => "f",
        "χ" => "x",
        "ψ" => "ps",
        "ω" => "o",
        "ώ" => "o",
        "ὠ" => "o",
        "ὡ" => "o",
        "ὢ" => "o",
        "ὣ" => "o",
        "ὤ" => "o",
        "ὥ" => "o",
        "ὦ" => "o",
        "ὧ" => "o",
        "ᾠ" => "o",
        "ᾡ" => "o",
        "ᾢ" => "o",
        "ᾣ" => "o",
        "ᾤ" => "o",
        "ᾥ" => "o",
        "ᾦ" => "o",
        "ᾧ" => "o",
        "ὼ" => "o",
        "ῲ" => "o",
        "ῳ" => "o",
        "ῴ" => "o",
        "ῶ" => "o",
        "ῷ" => "o",
        "¨" => "",
        "΅" => "",
        "᾿" => "",
        "῾" => "",
        "῍" => "",
        "῝" => "",
        "῎" => "",
        "῞" => "",
        "῏" => "",
        "῟" => "",
        "῀" => "",
        "῁" => "",
        "΄" => "",
        "`" => "",
        "῭" => "",
        "ͺ" => "",
        "᾽" => "",
        "А" => "A",
        "Б" => "B",
        "В" => "V",
        "Г" => "G",
        "Д" => "D",
        "Е" => "E",
        "Ё" => "E",
        "Ж" => "ZH",
        "З" => "Z",
        "И" => "I",
        "Й" => "I",
        "К" => "K",
        "Л" => "L",
        "М" => "M",
        "Н" => "N",
        "О" => "O",
        "П" => "P",
        "Р" => "R",
        "С" => "S",
        "Т" => "T",
        "У" => "U",
        "Ф" => "F",
        "Х" => "KH",
        "Ц" => "TS",
        "Ч" => "CH",
        "Ш" => "SH",
        "Щ" => "SHCH",
        "Ы" => "Y",
        "Э" => "E",
        "Ю" => "YU",
        "Я" => "YA",
        "а" => "A",
        "б" => "B",
        "в" => "V",
        "г" => "G",
        "д" => "D",
        "е" => "E",
        "ё" => "E",
        "ж" => "ZH",
        "з" => "Z",
        "и" => "I",
        "й" => "I",
        "к" => "K",
        "л" => "L",
        "м" => "M",
        "н" => "N",
        "о" => "O",
        "п" => "P",
        "р" => "R",
        "с" => "S",
        "т" => "T",
        "у" => "U",
        "ф" => "F",
        "х" => "KH",
        "ц" => "TS",
        "ч" => "CH",
        "ш" => "SH",
        "щ" => "SHCH",
        "ы" => "Y",
        "э" => "E",
        "ю" => "YU",
        "я" => "YA",
        "Ъ" => "",
        "ъ" => "",
        "Ь" => "",
        "ь" => "",
        "ð" => "d",
        "Ð" => "D",
        "þ" => "th",
        "Þ" => "TH",
    ];

    public static $punctuations = [
        "double_quotes" => '"',
        "quotes" => "'",
        "backtick" => "`",
        "comma" => ",",
        "period" => ".",
        "hyphen" => "-",
        "underscore" => "_",
        "colon" => ":",
        "semicolon" => ";",
        "pipe" => "|",
        "left_curly" => "{",
        "left_square" => "[",
        "right_curly" => "}",
        "right_square" => "]",
        "plus" => "+",
        "equal" => "=",
        "asterisk" => "*",
        "ampersand" => "&",
        "percent" => "%",
        "caret" => "^",
        "dollar" => "$",
        "hash" => "#",
        "at" => "@",
        "exclamation" => "!",
        "tilde" => "~",
        "left_parenthesis" => "(",
        "right_parenthesis" => ")",
        "question_mark" => "?",
        "less_than" => "<",
        "greater_than" => ">",
        "back_slash" => '\\',
        "number" => "№",
        "left_arrow" => "«",
        "right_arrow" => "»",
        "quote" => '"',
        "dot" => ".",
        "treedot" => "…",
        "quote1" => "”",
        "quote2" => "“",
        "tiredash" => "-—-",
        "tripletire" => "---",
        "trademark" => "®",
        "copyright" => "©",
        "altertire" => "–"
    ];
}

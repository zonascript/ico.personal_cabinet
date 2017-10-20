<?php

namespace Xcart\App\Form;

/**
 * Class PrepareData
 * @package Xcart\App\Form
 */
class PrepareData
{
    public static function collect(array $post, array $files, $fixFiles = true)
    {
        return static::merge($fixFiles ? static::fixFiles($files) : $files, $post, true);
    }

    /**
     * Zend Framework (http://framework.zend.com/)
     *
     * @link http://github.com/zendframework/zf2 for the canonical source repository
     * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
     * @license http://framework.zend.com/license/new-bsd New BSD License
     *
     * Code from https://github.com/zendframework/zf2/blob/master/library/Zend/Stdlib/ArrayUtils.php#L245
     *
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays and preserveNumericKeys is false, the value
     * from the second array will be appended to the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the one of the first array.
     *
     * @param  array $a
     * @param  array $b
     * @param  bool $preserveNumericKeys
     * @return array
     */
    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key) && !$preserveNumericKeys) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * Fix wrong $_FILES array
     * @param $data
     * @return array
     */
    public static function fixFiles($data)
    {
        $n = [];
        foreach ($data as $baseName => $params) {
            foreach ($params as $innerKey => $value) {
                foreach ($value as $inlineName => $item) {
                    if (is_array($item)) {
                        /**
                         * @TODO I HATE THIS FUCKING PHP LOGIC.
                         *
                         * $index = key($item);
                         * $key = key($item[$index]);
                         * $n[$baseName][$inlineName][$index][$key][$innerKey] = $item[$index][$key];
                         */
                        foreach($item as $index => $t) {
                            $key = key($t);
                            $n[$baseName][$inlineName][$index][$key][$innerKey] = $t[$key];
                        }
                    } else {
                        $n[$baseName][$inlineName][$innerKey] = $item;
                    }
                }
            }
        }
        return $n;
    }

    public static function checkFilesStruct($data)
    {
        if (is_array($data) &&
            isset($data['error']) &&
            isset($data['tmp_name']) &&
            isset($data['size']) &&
            isset($data['name']) &&
            isset($data['type']))

        {
            return true;
        }

        return false;
    }
}
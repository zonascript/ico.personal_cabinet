<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 12/09/14.09.2014 16:54
 */

namespace Modules\Core\Components;

use Mindy\Base\Mindy;

/**
 * Class ParamsHelper
 * @package Modules\Core\Components
 */
class ParamsHelper
{
    /**
     * @param $name
     * @return string|null
     */
    public static function get($name, $defaultValue = null)
    {
        $value = Mindy::app()->cache->get($name);
        if (!$value) {
            $value = self::fetchSetting($name);
        }
        return $value ? $value : $defaultValue;
    }

    /**
     * @param $name
     * @return null
     */
    protected static function fetchSetting($name)
    {
        if (substr_count($name, '.') != 2) {
            return null;
        }
        list($module, $model, $param) = explode('.', $name);
        $module = ucfirst($module);
        $model = ucfirst($model);
        /** @var \Modules\Core\Models\SettingsModel $className */
        $className = '\\Modules\\' . $module . '\\Models\\' . $model . 'Settings';
        $data = $className::getInstance(true);
        return isset($data[$param]) ? $data[$param] : null;
    }
}

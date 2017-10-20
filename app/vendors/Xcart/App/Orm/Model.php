<?php

namespace Xcart\App\Orm;

use ReflectionClass;

/**
 * Class Model
 * @package Xcart\App\Orm
 */
class Model extends AbstractModel
{
//    use LegacyMethodsTrait;

    private $cache_time = 0;

    /**
     * @return string
     */
    public static function tableName()
    {
        $bundleName = self::getBundleName();
        if (!empty($bundleName)) {
            return sprintf("%s_%s",
                self::normalizeTableName(str_replace('Bundle', '', $bundleName)),
                parent::tableName()
            );
        } else {
            return parent::tableName();
        }
    }

    /**
     * Return module name
     * @return string
     */
    public static function getBundleName()
    {
        $object = new ReflectionClass(get_called_class());

        // For classical modules
        if ($pos = strpos($object->getFileName(), 'Modules')) {
            $shortPath = substr($object->getFileName(), $pos + 8);
            return substr($shortPath, 0, strpos($shortPath, '/'));
        }

        // For symphony bundles
        if ($pos = strpos($object->getFileName(), 'Bundle')) {
            $shortPath = substr($object->getFileName(), $pos + 7);
            return substr($shortPath, 0, strpos($shortPath, '/'));
        }

        return '';
    }

    public function getObjects($instance = null)
    {
        return static::objects(($instance) ? $instance : $this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getShortName();
    }

    public function getCache() {
        return $this->cache_time;
    }

    public function cache($life_time = 30) {
        $this->cache_time = $life_time;
        return $this;
    }

    public function noCache() {
        $this->cache_time = 0;
        return $this;
    }
}

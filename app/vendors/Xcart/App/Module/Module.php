<?php
namespace Xcart\App\Module;


use Xcart\App\Helpers\ClassNames;
use Xcart\App\Helpers\SmartProperties;
use ReflectionClass;
use Xcart\App\Main\Xcart;
use Xcart\App\Translate\Translate;

abstract class Module
{
    protected static $_paths = [];

    use ClassNames, SmartProperties;

    public static function onApplicationInit()
    {
    }

    public static function onApplicationRun()
    {
    }

    public static function onApplicationEnd()
    {
    }

    public static function getVerboseName()
    {
        return static::getName();
    }

    public static function getName()
    {
        return str_replace('Module', '', static::classNameShort());
    }

    public static function getPath()
    {
        $class = static::className();
        if (!isset(static::$_paths[$class])) {
            $rc = new ReflectionClass($class);
            static::$_paths[$class] = dirname($rc->getFileName());
        }
        return static::$_paths[$class];
    }

    public static function getAdminMenu()
    {
        return [];
    }

    public static function setComponent($name, $component)
    {
        Xcart::app()->setComponent($name, $component);
    }

    public static function getComponent($name)
    {
        return Xcart::app()->getComponent($name);
    }

    public static function t($str, $params = [], $dic = 'main')
    {
        return Translate::getInstance()->t(get_called_class() . "." . $dic, $str, $params);
    }
}
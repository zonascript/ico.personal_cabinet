<?php

namespace Modules\Admin\Traits;


use Modules\Admin\Contrib\Admin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Xcart\App\Main\Xcart;

/**
 * Class AdminTrait
 * @package Modules\Admin\Traits
 */
trait AdminTrait
{
    public static $adminFolder = 'Admin';

    public static function getAdminMenu()
    {
        $menu = [];
        $adminClasses = static::getAdminClasses();
        foreach ($adminClasses as $adminClass) {
            if (is_a($adminClass, Admin::className(), true) && $adminClass::$public) {
                $menu[] = [
                    'adminClassName' => $adminClass::className(),
                    'adminClassNameShort' => $adminClass::classNameShort(),
                    'moduleName' => static::getName(),
                    'name' => $adminClass::getName(),
                    'route' => Xcart::app()->router->url('admin:all', [
                        'module' => static::getName(),
                        'admin' => $adminClass::classNameShort()
                    ])
                ];
            }
        }
        return $menu;
    }

    public static function getAdminClasses()
    {
        $classes = [];
        $modulePath = self::getPath();
        $path = implode(DIRECTORY_SEPARATOR, [$modulePath, static::$adminFolder]);
        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
            {
                if ($filename->isDir()) continue;
                $name = $filename->getBasename('.php');
                $classes[] = implode('\\', ['Modules', static::getName(), static::$adminFolder, $name]);
            }
        }
        return $classes;
    }
}
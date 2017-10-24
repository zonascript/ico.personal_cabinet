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
 * @date 12/07/14.07.2014 14:13
 */

namespace Modules\Core\Components;


use Mindy\Base\Mindy;
use Mindy\Helper\Alias;
use Mindy\Helper\FileHelper;

class ModuleManager
{
    /**
     * @param $name
     * @return mixed
     */
    public static function install($name)
    {
        $core = Mindy::app()->getModule('Core');

        $hasModule = Mindy::app()->getModule($name);
        if($hasModule) {
            return false;
        } else {
            $version = $core->update->install($name);
            $module = Mindy::app()->getModule($name);
            if($version && $module) {
                $module->install();
            }
        }

        return $version;
    }

    /**
     * @param $name
     * @param null $updateToVersion
     * @return mixed
     */
    public static function update($name, $updateToVersion = null)
    {
        $core = Mindy::app()->getModule('Core');
        $module = Mindy::app()->getModule($name);
        $currentVersion = $module->getVersion();
        $version = $core->update->update($name, $currentVersion, $updateToVersion);
        if($version && $module) {
            $module->update();
        }
        return $version;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function delete($name)
    {
        $module = Mindy::app()->getModule($name);
        if($module) {
            $module->delete();
            $path = $module->getModulePath();
        } else {
            $path = Alias::get('Modules.' . $name);
        }
        FileHelper::removeDirectory($path);
        return !is_dir($path);
    }
}

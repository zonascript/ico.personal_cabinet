<?php
/**
 * Created by max
 * Date: 27/10/15
 * Time: 16:11
 */

namespace Modules\Core\Helpers;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Orm\Sync;

class DatabaseHelper
{
    protected static function getModels($module = null)
    {
        $app = Mindy::app();
        $models = [];
        if ($module === null) {
            $modules = $app->getModules();
            foreach ($modules as $name => $config) {
                /** @var \Mindy\Base\Module $module */
                $module = $app->getModule($name);
                $tmp = $module->getModels();
                $models = array_merge($models, $tmp);
            }
        } else {
            if ($app->hasModule($module)) {
                $module = $app->getModule($module);
                $models = $module->getModels();
            } else {
                throw new Exception('Module not found');
            }
        }

        return array_values($models);
    }

    public static function drop($module = null)
    {
        $models = self::getModels($module);
        $sync = new Sync($models);
        return $sync->delete();
    }

    public static function sync($module = null)
    {
        $models = self::getModels($module);
        $sync = new Sync($models);
        return $sync->create();
    }
}
<?php

namespace Modules\Core\Commands;

use Mindy\Base\Mindy;
use Mindy\Console\ConsoleCommand;
use Mindy\Helper\Console;
use Mindy\Orm\Sync;

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
 * @date 28/04/14.04.2014 17:53
 */
class DbCommand extends ConsoleCommand
{
    protected function getModels($module = null)
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
                echo Console::color(ucfirst($module->getId()) . ': ' . count($tmp), Console::FOREGROUND_YELLOW) . PHP_EOL;
            }
        } else {
            if ($app->hasModule($module)) {
                $module = $app->getModule($module);
                $models = $module->getModels();
                echo Console::color(ucfirst($module->getId()) . ': ' . count($models), Console::FOREGROUND_YELLOW) . PHP_EOL;
            } else {
                echo Console::color("Module not found or not enabled", Console::FOREGROUND_LIGHT_RED) . PHP_EOL;
                exit(1);
            }
        }

        return array_values($models);
    }

    public function actionDrop($module = null)
    {
        $models = $this->getModels($module);
        $sync = new Sync($models);
        $deleted = $sync->delete();
        echo PHP_EOL . Console::color("Deleted: " . count($deleted), Console::FOREGROUND_LIGHT_RED) . PHP_EOL;
        foreach ($deleted as $table) {
            echo $table . PHP_EOL;
        }
    }

    public function actionSync($module = null)
    {
        $models = $this->getModels($module);
        $sync = new Sync($models);
        $created = $sync->create();
        echo PHP_EOL . Console::color("Created: " . count($created), Console::FOREGROUND_LIGHT_RED) . PHP_EOL;
        foreach ($created as $table) {
            echo $table . PHP_EOL;
        }
    }
}

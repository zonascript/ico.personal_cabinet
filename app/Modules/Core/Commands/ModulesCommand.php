<?php

namespace Modules\Core\Commands;

use Mindy\Console\ConsoleCommand;
use Mindy\Helper\Console;
use Modules\Core\Components\ModuleManager;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 28/04/14.04.2014 17:53
 */
class ModulesCommand extends ConsoleCommand
{
    public function actionInstall($module)
    {
        if ($version = ModuleManager::install($module)) {
            echo Console::color("Success. Installed version: " . $version, Console::FOREGROUND_GREEN) . PHP_EOL;
        } else {
            echo Console::color("Failed to install version: " . $version, Console::FOREGROUND_RED) . PHP_EOL;
        }
    }

    public function actionUpdate($module, $updateToVersion = null)
    {
        if ($version = ModuleManager::update($module, $updateToVersion)) {
            echo Console::color("Success. Installed version: " . $version, Console::FOREGROUND_GREEN) . PHP_EOL;
        } else {
            echo Console::color("Failed to install version: " . $version, Console::FOREGROUND_RED) . PHP_EOL;
        }
    }

    public function actionDelete($module)
    {
        if (ModuleManager::delete($module)) {
            echo Console::color("Success.", Console::FOREGROUND_GREEN) . PHP_EOL;
        } else {
            echo Console::color("Failed.", Console::FOREGROUND_RED) . PHP_EOL;
        }
    }
}

<?php
namespace Modules\Core\Helpers;

use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class AdminHelper
{
    public static function getMenu()
    {
        $menu = [];
        $modules = Xcart::app()->getModulesConfig();

        foreach ($modules as $name => $config) {
            if (isset($config['class'])) {
                /** @var Module $class */
                $class = $config['class'];
                $moduleMenu = $class::getAdminMenu();
                if ($moduleMenu) {
                    $menu[] = [
                        'name' => $class::getVerboseName(),
                        'key' => $name,
                        'class' => $config['class'],
                        'items' => $moduleMenu
                    ];
                }
            }
        }

        return $menu;
    }
}
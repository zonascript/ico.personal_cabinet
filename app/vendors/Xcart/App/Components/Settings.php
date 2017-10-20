<?php

namespace Xcart\App\Components;


use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class Settings
{
    public function get($name)
    {
        $info = explode('.', $name);
        if (count($info) == 2) {
            $moduleName = $info[0];
            $attributeName = $info[1];
            /** @var Module $module */
            $module = Xcart::app()->getModule($moduleName);
            if ($module && $attributeName) {
                return $module->getSetting($attributeName);
            }
        }
        return null;
    }
}
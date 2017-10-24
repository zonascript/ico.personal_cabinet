<?php

namespace Modules\Admin;

use Mindy\Base\Module;
use Mindy\Helper\Alias;

class AdminModule extends Module
{
    protected $dashboards = [];

    public function getDashboardClasses()
    {
        if (empty($this->dashboards)) {
            $path = Alias::get('application.config.dashboard') . '.php';
            if (is_file($path)) {
                $this->dashboards = include_once($path);
            }
        }
        return $this->dashboards;
    }
}

<?php

namespace Xcart\App\Main;

use Xcart\App\Application\Application;
use Xcart\App\Helpers\Creator;

if (!function_exists('d')) {
    require_once __DIR__ . '/dump.php';
}

class Xcart
{
    public static function getVersion()
    {
        return '0.1';
    }

    /** @var \Xcart\App\Application\Application */
    protected static $_app;

    public static function init($configuration, $application = 'Xcart\App\Application\Application')
    {
        static::$_app = Creator::create($application, $configuration);
    }

    /**
     * @return \Xcart\App\Application\Application
     */
    public static function app()
    {
        return static::$_app;
    }
}
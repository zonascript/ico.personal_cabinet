<?php
namespace Modules\Core;

use Detection\MobileDetect;
use Fenom;
use Modules\Core\Components\GlobalConfig;
use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class CoreModule extends Module
{
    private static $config;

    public static function getGlobalConfig()
    {
        if (!self::$config) {
            self::$config = GlobalConfig::getInstance()->setOldMode();
        }

        return self::$config;
    }

    public static function onApplicationRun()
    {
        $template = Xcart::app()->template->getRenderer();

        $template->addModifier('br2nl', function($str)
        {
            return trim(preg_replace("=<br */?>=i", "\n", $str));
        });

        $template->addModifier('nl2space', function($str)
        {
            return preg_replace("/(\r\n|\n|\r)/", " ", $str);
        });

        $template->addModifier('instanceof', function($obj, $class_name)
        {
            return $obj instanceof $class_name;
        });

        $template->addBlockFunction('smarty_admin_block', function ($params, $html) {

            $params['html'] = $html;

            echo Xcart::app()->template->render('smarty_like/admin_block.tpl', $params);
        });


        $template->addAccessorSmart("global_config", "config", Fenom::ACCESSOR_PROPERTY);
        $template->global_config = self::getGlobalConfig();

        $template->addAccessorSmart("detector", "detector", Fenom::ACCESSOR_PROPERTY);
        $template->detector = new MobileDetect();
    }
}
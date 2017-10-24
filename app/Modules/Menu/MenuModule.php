<?php

namespace Modules\Menu;

use Mindy\Base\Mindy;
use Mindy\Base\Module;

class MenuModule extends Module
{
    public $defaultController = 'menu';

    public static function preConfigure()
    {
        $tpl = Mindy::app()->template;
        $tpl->addHelper('get_menu', ['\Modules\Menu\Helpers\MenuHelper', 'renderMenu']);
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => $this->getName(),
                    'adminClass' => 'MenuAdmin',
                ],
            ]
        ];
    }
}

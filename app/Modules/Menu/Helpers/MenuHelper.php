<?php

namespace Modules\Menu\Helpers;

use Mindy\Utils\RenderTrait;
use Modules\Menu\Models\Menu;

class MenuHelper
{
    use RenderTrait;

    public static function renderMenu($slug, $template = "menu/menu.html")
    {
        $menu = Menu::objects()->get(['slug' => $slug]);
        if ($menu) {
            return self::renderTemplate($template, [
                'items' => $menu->tree()->descendants()->asTree()->all()
            ]);
        } else {
            return '';
        }
    }
} 

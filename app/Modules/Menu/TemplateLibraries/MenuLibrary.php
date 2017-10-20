<?php

namespace Modules\Menu\TemplateLibraries;

use Xcart\App\Template\TemplateLibrary;
use Xcart\App\Traits\RenderTrait;

class MenuLibrary extends TemplateLibrary
{
    use RenderTrait;

    public static $template = 'menu/menu.tpl';

    /**
     * @kind function
     * @name get_menu
     * @return string
     */
    public static function getMenu($params)
    {
        if (empty($params['code'])) {
            return '';
        }

        $code = $params['code'];
        $template = self::$template;

        if (!empty($params['template'])) {
            $template = $params['template'];
        }

        if ($items = self::getData($code)) {
            return self::renderTemplate($template, [
                'items' => $items
            ]);
        }

        return '';
    }

    /**
     * @kind accessorFunction
     * @name get_menu_items
     * @return array
     */
    public static function getMenuItems($code)
    {
        if (!$code) {
            return [];
        }

        return self::getData($code);
    }

    public static function getData($code)
    {
        if ($code == 'main-menu') {
            return [
                [
                    'url' => '/',
                    'name' => 'Our stores',
                    'class' => '',
                    'items' => [],
                ],
                [
                    'url' => '/shop',
                    'name' => 'Shop',
                    'class' => '',
                    'items' => [],
                ],
                [
                    'url' => '/s3-on-amazon',
                    'name' => 'S3 on Amazon',
                    'class' => '',
                    'items' => [],
                ],
                [
                    'url' => '/business-relations',
                    'name' => 'Business Relations',
                    'class' => '',
                    'items' => [],
                ],
                [
                    'url' => '/about-us',
                    'name' => 'About Us',
                    'class' => '',
                    'items' => [],
                ],
            ];
        }
        else if ('footer-menu') {
            return [
                [
                    'url' => '/terms-of-use',
                    'name' => 'Terms of use',
                    'items' => []
                ],
                [
                    'url' => '/privacy-policy',
                    'name' => 'Privacy policy',
                    'items' => [],
                ],
            ];
        }
        return [];
    }
}
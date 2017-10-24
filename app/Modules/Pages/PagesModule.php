<?php

namespace Modules\Pages;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Modules\Pages\Models\Block;
use Modules\Pages\Models\Page;

/**
 * Class PagesModule
 * @package Modules\Pages
 */
class PagesModule extends Module
{
    public $sizes = [
        'thumb' => [
            160, 104,
            'method' => 'adaptiveResizeFromTop',
            'options' => ['jpeg_quality' => 5]
        ],
        'resize' => [
            978
        ],
    ];

    public static function preConfigure()
    {
        $tpl = Mindy::app()->template;
        $tpl->addHelper('get_block', function ($slug) {
            $model = Block::objects()->get(['slug' => $slug]);
            return $model === null ? null : $model;
        });
        $tpl->addHelper('get_pages', function ($parentId, $limit = 10, $offset = 0, $order = []) {
            return Page::objects()->filter([
                'parent_id' => $parentId
            ])->limit($limit)->offset($offset)->order($order)->all();
        });
    }

    public function getVersion()
    {
        return '1.0';
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Pages'),
                    'adminClass' => 'PageAdmin',
                ],
                [
                    'name' => self::t('Text blocks'),
                    'adminClass' => 'BlockAdmin',
                ],
            ]
        ];
    }
}

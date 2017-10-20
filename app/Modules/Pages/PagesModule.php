<?php

namespace Modules\Pages;

use Modules\Admin\Traits\AdminTrait;
use Xcart\App\Module\Module;

class PagesModule extends Module
{
    use AdminTrait;

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
}
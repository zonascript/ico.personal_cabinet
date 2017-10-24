<?php

namespace Modules\Translate;

use Mindy\Base\Module;

class TranslateModule extends Module
{
    public function getVersion()
    {
        return '0.2';
    }

    public function getMenu()
    {
        return [
            'name' => self::t('Translate'),
            'items' => [
                [
                    'name' => self::t('Translate'),
                    'url' => 'translate:index',
                    'icon' => 'icon-translate'
                ]
            ]
        ];
    }
}

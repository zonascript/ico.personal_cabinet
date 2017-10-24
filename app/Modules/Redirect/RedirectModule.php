<?php

namespace Modules\Redirect;

use Mindy\Base\Module;

class RedirectModule extends Module
{
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
                    'name' => self::t('Redirect'),
                    'adminClass' => 'RedirectAdmin'
                ]
            ]
        ];
    }
}

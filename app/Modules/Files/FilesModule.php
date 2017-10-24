<?php

namespace Modules\Files;

use Mindy\Base\Module;

class FilesModule extends Module
{
    public function getVersion()
    {
        return '1.0';
    }

    public function getName()
    {
        return $this->t('Files');
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Files'),
                    'url' => 'files:index'
                ]
            ]
        ];
    }
}

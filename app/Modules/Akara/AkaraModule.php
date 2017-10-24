<?php

namespace Modules\Akara;


use Mindy\Base\Module;

class AkaraModule extends Module
{
    public function getMenu()
    {
        return [
            'name' => self::t('ICO'),
            'items' => [
                [
                    'name' => self::t('ICO'),
                    'adminClass' => 'IcoAdmin',
                ],
                [
                    'name' => self::t('Bonuses'),
                    'adminClass' => 'BonusAdmin',
                ],
                [
                    'name' => self::t('Coins'),
                    'adminClass' => 'CoinAdmin',
                ],
                [
                    'name' => self::t('Transactions'),
                    'adminClass' => 'TransactionAdmin',
                ],
                [
                    'name' => self::t('Pool'),
                    'adminClass' => 'PoolAdmin',
                ]
            ]
        ];
    }
}
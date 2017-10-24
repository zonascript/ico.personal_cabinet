<?php

namespace Modules\Redirect\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;
use Modules\Redirect\RedirectModule;

/**
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 14/04/14.04.2014 15:11
 */

class Redirect extends Model
{
    public function __toString()
    {
        return strtr("{from} - {to} ({type})", [
            '{from}' => $this->from_url,
            '{to}' => $this->to_url,
            '{type}' => $this->type
        ]);
    }

    public static function getFields()
    {
        return [
            'from_url' => [
                'class' => CharField::className(),
                'verboseName' => RedirectModule::t('From url')
            ],
            'to_url' => [
                'class' => CharField::className(),
                'verboseName' => RedirectModule::t('To url')
            ],
            'type' => [
                'class' => IntField::className(),
                'length' => 3,
                'verboseName' => RedirectModule::t('Type'),
                'choices' => [
                    '301' => '301',
                    '302' => '302',
                ]
            ]
        ];
    }
}

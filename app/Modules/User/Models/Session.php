<?php

namespace Modules\User\Models;

use Mindy\Orm\Fields\BlobField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;
use Modules\User\UserModule;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/05/14.05.2014 14:00
 */

/**
 * Class Session
 * @package Modules\User
 */
class Session extends Model
{
    public static function getFields()
    {
        return [
            'id' => [
                'class' => CharField::className(),
                'length' => 32,
                'primary' => true,
                'null' => false,
            ],
            'expire' => [
                'class' => IntField::className(),
                'null' => false,
                'verboseName' => UserModule::t("Expire time"),
            ],
            'data' => [
                'class' => BlobField::className(),
                'null' => true,
                'verboseName' => UserModule::t("Session data"),
            ]
        ];
    }

    public function __toString()
    {
        return (string)$this->id;
    }
}

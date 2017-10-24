<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 10/12/14 14:07
 */

namespace Modules\User\Commands;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateField;
use Mindy\Orm\Fields\EmailField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;

class BitrixUser extends Model
{
    public static function getFields()
    {
        return [
            'LOGIN' => [
                'class' => CharField::className(),
            ],
            'PASSWORD' => [
                'class' => CharField::className(),
            ],
            'CHECKWORD' => [
                'class' => CharField::className(),
            ],
            'NAME' => [
                'class' => CharField::className(),
            ],
            'LAST_NAME' => [
                'class' => CharField::className(),
            ],
            'SECOND_NAME' => [
                'class' => CharField::className(),
            ],
            'EMAIL' => [
                'class' => EmailField::className(),
            ],
            'EXTERNAL_AUTH_ID' => [
                'class' => CharField::className(),
            ],
            'XML_ID' => [
                'class' => CharField::className(),
            ],
            'PERSONAL_COUNTRY' => [
                'class' => CharField::className(),
            ],
            'PERSONAL_PHONE' => [
                'class' => CharField::className(),
            ],
            'PERSONAL_MOBILE' => [
                'class' => CharField::className(),
            ],
            'PERSONAL_CITY' => [
                'class' => CharField::className(),
            ],
            'ACTIVE' => [
                'class' => CharField::className(),
            ],
            'PERSONAL_BIRTHDAY' => [
                'class' => DateField::className(),
            ],
            'PERSONAL_NOTES' => [
                'class' => TextField::className(),
            ],
            'PERSONAL_STREET' => [
                'class' => TextField::className(),
            ]
        ];
    }

    public static function tableName()
    {
        return 'b_user';
    }

    public function getIsActive()
    {
        return $this->ACTIVE == 'Y';
    }
}
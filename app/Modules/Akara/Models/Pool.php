<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;
use Modules\User\Models\User;

class Pool extends Model
{
    public static function getFields()
    {
        return [
            'token' => [
                'class' => CharField::className(),
            ],
            'coin' => [
                'class' => ForeignField::className(),
                'modelClass' => Coin::className(),
                'verboseName' => AkaraModule::t("Coin"),
            ],
            'user' => [
                'class' => ForeignField::className(),
                'modelClass' => User::className(),
                'null' => true,
                'verboseName' => AkaraModule::t("User"),
            ]
        ];
    }
}
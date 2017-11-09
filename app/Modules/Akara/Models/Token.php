<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;
use Modules\User\Models\User;

class Token extends Model
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
            'ico' => [
                'class' => ForeignField::className(),
                'modelClass' => Ico::className(),
                'null' => true,
                'verboseName' => AkaraModule::t("Ico"),
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
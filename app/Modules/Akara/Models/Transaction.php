<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\DecimalField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;
use Modules\User\Models\User;

class Transaction extends Model
{
    public static function getFields()
    {
        return [
            'ico' => [
                'class' => ForeignField::className(),
                'modelClass' => Ico::className(),
                'verboseName' => AkaraModule::t("ICO"),
            ],
            'coin' => [
                'class' => ForeignField::className(),
                'modelClass' => Coin::className(),
                'verboseName' => AkaraModule::t("Coin"),
            ],
            'user' => [
                'class' => ForeignField::className(),
                'modelClass' => User::className(),
                'verboseName' => AkaraModule::t("User"),
            ],
            'amount' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Amount"),
            ],
            'base_conversion_rate' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Conversion rate"),
            ],
            'bonus' => [
                'class' => ForeignField::className(),
                'modelClass' => Bonus::className(),
                'verboseName' => AkaraModule::t("Bonus"),
                'null' => true
            ],
            'type' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Type"),
                'choices' => [
                    'purchase' => AkaraModule::t('Purchase'),
                    'withdraw' => AkaraModule::t('Withdraw')
                ]
            ],
            'status' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Status"),
                'choices' => [
                    'pending' => AkaraModule::t('Pending'),
                    'complete' => AkaraModule::t('Complete')
                ]
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'verboseName' => AkaraModule::t('Created at')
            ],
            'updated_at' => [
                'class' => DateTimeField::className(),
                'autoNow' => true,
                'verboseName' => AkaraModule::t("Updated at"),
            ]
        ];
    }
}
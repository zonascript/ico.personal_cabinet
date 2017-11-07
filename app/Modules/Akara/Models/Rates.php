<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\DecimalField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;

class Rates extends Model
{
    public static function getFields()
    {
        return [
            'coin' => [
                'class' => ForeignField::className(),
                'modelClass' => Coin::className(),
                'verboseName' => AkaraModule::t("Coin"),
            ],
            'ico' => [
                'class' => ForeignField::className(),
                'modelClass' => Ico::className(),
                'verboseName' => AkaraModule::t("Ico"),
            ],
            'date_time' => [
                'class' => DateTimeField::className(),
                'verboseName' => AkaraModule::t("Date"),
                'autoNowAdd' => true,
                'autoNow' => true,
            ],

            'bittrex' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Bittrex"),
            ],

            'bitfinex' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Bitfinex"),
            ],

            'poloniex' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Poloniex"),
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
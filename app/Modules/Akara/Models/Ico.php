<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;

class Ico extends Model
{
    public function __toString()
    {
        return $this->name;
    }

    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Name"),
            ],
            'start_date' => [
                'class' => DateTimeField::className(),
                'verboseName' => AkaraModule::t("Start date"),
            ],
            'end_date' => [
                'class' => DateTimeField::className(),
                'verboseName' => AkaraModule::t("End date"),
            ],
            'coin' => [
                'class' => ForeignField::className(),
                'modelClass' => Coin::className(),
                'verboseName' => AkaraModule::t("Base Coin"),
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
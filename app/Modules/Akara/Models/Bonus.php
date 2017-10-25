<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\DecimalField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;

class Bonus extends Model
{
    public function __toString()
    {
        return "{$this->name} ({$this->percent}%)";
    }

    public static function getFields()
    {
        return [
            'ico' => [
                'class' => ForeignField::className(),
                'modelClass' => Ico::className(),
                'verboseName' => AkaraModule::t("Ico"),
            ],
            'end_date' => [
                'class' => DateTimeField::className(),
                'verboseName' => AkaraModule::t("End date"),
            ],
            'percent' => [
                'class' => DecimalField::className(),
                'null' => false,
                'default' => 0,
                'verboseName' => AkaraModule::t("Bonus percent"),
            ],
            'name' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Name"),
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
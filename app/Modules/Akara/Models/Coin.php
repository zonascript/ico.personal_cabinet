<?php

namespace Modules\Akara\Models;


use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DecimalField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\Akara\AkaraModule;

class Coin extends Model
{
    public function __toString()
    {
        return "{$this->name} ({$this->code})";
    }

    public static function getFields()
    {
        return [
            'code' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Ticker"),
            ],
            'name' => [
                'class' => CharField::className(),
                'verboseName' => AkaraModule::t("Name"),
            ],
            'description' => [
                'class' => TextField::className(),
                'verboseName' => AkaraModule::t("Description"),
                'null' => true,
            ],
            'min_amount' => [
                'class' => DecimalField::className(),
                'verboseName' => AkaraModule::t("Min amount"),
            ],
            'is_active' => [
                'class' => BooleanField::className(),
                'verboseName' => AkaraModule::t("Active"),
            ],
        ];
    }
}
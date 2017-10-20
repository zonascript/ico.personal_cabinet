<?php

namespace Modules\Core\Models;


use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;

class CountryModel extends AutoMetaModel
{
    public static $codes = [
        'United States' => 'US',
        'Canada' => 'CA',
    ];

    public static function tableName()
    {
        return 'xcart_countries';
    }

    public static function getFields()
    {
        return [
            'code' => [
                'class' => AutoField::className(),
            ],
        ];
    }
}
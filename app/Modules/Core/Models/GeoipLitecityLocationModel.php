<?php

namespace Modules\Core\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;

class GeoipLitecityLocationModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_geo_litecity_location';
    }

    public static function getFields()
    {
        return [
            'locId' => [
                'class' => AutoField::className(),
            ],
            'region' => [
                'class' => CharField::className(),
                'default' => '',
                'null' => false
            ],
            'country' => [
                'class' => CharField::className(),
                'default' => '',
                'null' => false
            ],
            'country_model' => [
                'field' => 'country',
                'class' => ForeignField::className(),
                'modelClass' => CountryModel::className(),
                'link' => ['country' => 'code'],
            ],
            'state_model' => [
                'field' => 'region',
                'class' => ForeignField::className(),
                'modelClass' => StateModel::className(),
                'link' => [
                    'region' => 'code',
                    'country' => 'country_code'
                ],
            ],
            'blocks' => [
                'field' => 'locId',
                'class' => HasManyField::className(),
                'modelClass' => GeoLitecityBlocks::className(),
                'link' => ['locId' => 'locId'],
            ],
        ];
    }
}
<?php

namespace Modules\Core\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\IntField;

class GeoLitecityBlocks extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_geo_litecity_blocks';
    }

    public static function getFields()
    {
        return [
            'startIpNum' => [
                'class' => IntField::className(),
                'primary' => true
            ],
        ];
    }
}
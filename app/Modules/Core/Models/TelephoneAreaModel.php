<?php

namespace Modules\Core\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;

class TelephoneAreaModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_Telephone_area_codes';
    }

    public static function getFields()
    {
        return [
            'id' => [
                'class' => AutoField::className(),
            ],
        ];
    }
}
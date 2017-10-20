<?php

namespace Modules\Core\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;

class StateModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_states';
    }

    public static function getFields()
    {
        return [
            'stateid' => [
                'class' => AutoField::className(),
            ],
        ];
    }
}
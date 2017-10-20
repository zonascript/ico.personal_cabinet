<?php

namespace Modules\Product\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;

class ProductOptionModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_class_options';
    }

    public static  function getFields()
    {
        return [
            'optionid' => [
                'class' => AutoField::className(),
            ],
            'option_name' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'modified_price' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ]
        ];
    }
}
<?php

namespace Modules\Order\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;

class OrderFraudCheckModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_order_fraud_checks';
    }

    public static function getFields()
    {
        return [
            'id' => [
                'class' => AutoField::className(),
            ],
            'additional_info' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ]
        ];
    }
}
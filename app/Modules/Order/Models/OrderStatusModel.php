<?php

namespace Modules\Order\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\CharField;

class OrderStatusModel extends AutoMetaModel
{
    const ORDER_STATUS_AUTHORIZED = 'AP';
    const ORDER_STATUS_COMPLETED = 'P';

    public static function tableName()
    {
        return 'xcart_order_statuses';
    }
    public static function getFields()
    {
        return [
            'code' => [
                'class' => CharField::className(),
                'primary' => true
            ],
        ];
    }
}
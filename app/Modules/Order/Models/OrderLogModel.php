<?php

namespace Modules\Order\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\DateTimeField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Fields\TimestampField;
use Xcart\App\Orm\Model;

class OrderLogModel extends Model
{
    public static function tableName()
    {
        return 'xcart_order_logs';
    }

    public static function getFields()
    {
        return [
            'id' => AutoField::className(),
            'order' => [
                'field' => 'orderid',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'link' => ['orderid' => 'orderid'],
                'null' => false,
            ],
            'type' => [
                'class' => CharField::className(),
                'length' => 2,
                'default' => '',
                'null' => false,
            ],
            'date' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'autoNow' => true,
            ],
            'login' => [
                'class' => CharField::className(),
                'length' => 40,
                'default' => '',
                'null' => false,
            ],
            'log' => [
                'class' => TextField::className(),
                'null' => false,
            ]
        ];
    }
}
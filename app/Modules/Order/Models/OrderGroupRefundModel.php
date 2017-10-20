<?php

namespace Modules\Order\Models;


use Modules\Distributor\Models\DistributorModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\ForeignField;

class OrderGroupRefundModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_refund_groups';
    }

    public static function getFields()
    {
        return [
            'order' => [
                'field' => 'orderid',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'null' => false,
                'primary' => true,
            ],
            'manufacturer' => [
                'field' => 'manufacturerid',
                'class' => ForeignField::className(),
                'modelClass' => DistributorModel::className(),
                'null' => false,
                'primary' => true,
            ]
        ];
    }
}
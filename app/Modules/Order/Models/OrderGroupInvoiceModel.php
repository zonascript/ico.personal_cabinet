<?php
namespace Modules\Order\Models;

use Modules\Distributor\Models\DistributorModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\OrderGroupInvoice;

class OrderGroupInvoiceModel extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return OrderGroupInvoice::className();
    }

    public static function tableName()
    {
        return 'xcart_order_group_invoices';
    }

    public static function getFields()
    {
        return [
            'order' => [
                'field' => 'orderid',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'link' => ['orderid' => 'orderid'],
                'null' => false,
                'primary' => true,
            ],
            'manufacturer' => [
                'field' => 'manufacturerid',
                'class' => ForeignField::className(),
                'modelClass' => DistributorModel::className(),
                'link' => ['manufacturerid' => 'manufacturerid'],
                'null' => false,
                'primary' => true,
            ],
            'invoice_number' => [
                'class' => IntField::className(),
                'null' => false,
                'primary' => true,
                'default' => 0
            ],
        ];
    }
}
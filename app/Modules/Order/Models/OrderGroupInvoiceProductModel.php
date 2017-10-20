<?php
namespace Modules\Order\Models;

use Modules\Distributor\Models\DistributorModel;
use Modules\Product\Models\ProductModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TimestampField;

class OrderGroupInvoiceProductModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_order_group_invoices_products';
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
            'item' => [
                'field' => 'itemid',
                'class' => ForeignField::className(),
                'modelClass' => OrderDetailModel::className(),
                'link' => ['itemid' => 'itemid'],
                'null' => false,
                'primary' => true,
            ],
            'item_string' => [
                'field' => 'item_string',
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'primary' => true,
            ],
            'product' => [
                'field' => 'product_id',
                'class' => ForeignField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['product_id' => 'productid'],
                'null' => true,
                'default' => null,
            ],
            'updated_at' => [
                'class' => TimestampField::className(),
            ],
        ];
    }
}
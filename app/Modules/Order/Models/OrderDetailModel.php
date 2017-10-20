<?php
namespace Modules\Order\Models;

use Modules\Product\Models\ProductModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\OrderDetail;

class OrderDetailModel  extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return OrderDetail::className();
    }

    public static function tableName()
    {
        return 'xcart_order_details';
    }

    public static function getFields()
    {
        return [
            'itemid' => [
                'class' => AutoField::className(),
            ],
            'product_model' => [
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['productid' => 'productid'],
                'null' => false,
            ],
        ];
    }
}
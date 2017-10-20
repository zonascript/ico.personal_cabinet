<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class FilterProductModel extends Model
{
    public static function tableName()
    {
        return 'xcart_cidev_filter_products';
    }

    public static function getFields()
    {
        return [
            'filter_val' => [
                'field' => 'fv_id',
                'class' => ForeignField::className(),
                'modelClass' => FilterValueModel::className(),
                'link' => ['fv_id' => 'fv_id'],
                'null' => false,
                'default' => 0,
                'primary' => true
            ],
            'product' => [
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['productid' => 'productid'],
                'null' => false,
                'default' => 0,
                'primary' => true
            ],
            'is_feed' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
        ];
    }
}
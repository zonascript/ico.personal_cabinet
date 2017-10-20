<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\IntField;

/**
 * @property float price
 */
class PricingModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_pricing';
    }

    public static function getFields()
    {
        return [
            'priceid' => [
                'class' => AutoField::className(),
                'primary' => true,
                'null' => false,
            ],

            'product' => [
                'field' => 'productid',
                'class' => HasManyField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['productid' => 'productid']
            ]
        ];
    }
}
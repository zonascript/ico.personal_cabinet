<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class QuickPricingModel extends Model
{
    public static function tableName()
    {
        return "xcart_quick_prices";
    }

    public static function getFields()
    {
        return [
            'product' => [
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['productid' => 'productid'],
                'primary' => true
            ],
            'price' => [
                'field' => 'priceid',
                'class' => ForeignField::className(),
                'modelClass' => PricingModel::className(),
                'link' => ['priceid' => 'priceid', 'variantid' => 'variantid'],
                'primary' => true
            ],
            'variantid' => [
                'class' => IntField::className(),
                'primary' => true,
                'default' => 0,
            ],
            'membershipid' => [
                'class' => IntField::className(),
                'default' => 0,
            ],
        ];
    }
}
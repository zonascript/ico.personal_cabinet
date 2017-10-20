<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class ProductCategoriesModel extends Model
{
    public static function tableName()
    {
        return 'xcart_products_categories';
    }

    public static function getFields()
    {
        return [
            'category' => [
                'field' => 'categoryid',
                'class' => ForeignField::className(),
                'modelClass' => CategoryModel::className(),
                'link' => ['categoryid' => 'categoryid'],
                'primary' => true,
                'null' => false,
                'default' => 0
            ],
            'product' => [
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['productid' => 'productid'],
                'primary' => true,
                'null' => false,
                'default' => 0
            ],
            'main' => [
                'class' => CharField::className(),
                'primary' => true,
                'null' => false,
                'default' => 'N'
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
        ];
    }
}
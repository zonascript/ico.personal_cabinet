<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class ProductLinksModel extends Model
{
    public static function tableName()
    {
        return 'xcart_product_links';
    }

    public static function getFields()
    {
        return [
            'productid1' => [
                'class' => IntField::className(),
                'primary' => true,
                'null' => false,
                'default' => 0
            ],
            'productid2' => [
                'class' => IntField::className(),
                'primary' => true,
                'null' => false,
                'default' => 0
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
        ];
    }
}
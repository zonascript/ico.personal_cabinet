<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TimestampField;
use Xcart\App\Orm\Model;

class ProductUpcChangesModel extends Model
{
    public static function tableName()
    {
        return 'xcart_products_upc_changes';
    }

    public static function getFields()
    {
        return [
            'productid' => [
                'class' => IntField::className(),
                'primary' => true,
                'null' => false,
            ],
            'original_upc' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'corrected_upc' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'last_date' => [
                'class' => TimestampField::className(),
                'null' => false,
            ],
        ];
    }
}
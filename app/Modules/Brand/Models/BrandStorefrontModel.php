<?php
namespace Modules\Brand\Models;

use Modules\Sites\Models\SiteModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class BrandStorefrontModel extends Model
{
    public static function tableName()
    {
        return 'xcart_brands_sf';
    }

    public static function getFields()
    {
        return [
            'id' => [
                'class' => AutoField::className(),
            ],
            'brand' => [
                'field' => 'brandid',
                'class' => ForeignField::className(),
                'modelClass' => BrandModel::className(),
                'link' => ['brandid' => 'brandid'],
            ],
            'products_count' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
            'storefront' => [
                'field' => 'sfid',
                'class' => ForeignField::className(),
                'modelClass' => SiteModel::className(),
                'link' => ['sfid' => 'storefrontid'],
            ],
        ];
    }
}
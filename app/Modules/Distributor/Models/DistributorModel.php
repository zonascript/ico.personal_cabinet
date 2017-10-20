<?php

namespace Modules\Distributor\Models;

use Modules\Product\Models\ProductModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\Manufacturer;

class DistributorModel extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return Manufacturer::className();
    }

    public static function tableName()
    {
        return 'xcart_manufacturers';
    }

//    public static function getPrimaryKeyName($asArray = false)
//    {
//        return ['manufacturerid'];
//    }

    public static function getFields()
    {
        return [
            'manufacturerid' => [
                'class' => AutoField::className()
            ]
        ];
    }


    /**
     * @param ProductModel $modelProduct
     * @return float
     */
    public function calculatePrice($modelProduct)
    {
        $price = 0;
        if ($this->price_coef_z) {
            $price = max(round(($modelProduct->cost_to_us * $this->price_coef_x + $this->price_coef_y) / $this->price_coef_z, 2), $modelProduct->map_price);
        }
        return $price;
    }
}
<?php
namespace Modules\Shipping\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\Shipping;

class ShippingModel extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return Shipping::className();
    }

    public static function tableName()
    {
        return 'xcart_shipping';
    }

    public static function getFields()
    {
        return [
            'shippingid' => [
                'class' => AutoField::className()
            ],
            'important' => [
                'class' => IntField::className(),
                'length' => 1,
                'null' => false,
                'default' => 0,
                'chosen' => [
                    0 => 'No',
                    1 => 'Yes',
                ],
            ],
        ];
    }
}
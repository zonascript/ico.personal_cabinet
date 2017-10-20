<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class FilterValueModel extends Model
{
    public static function tableName()
    {
        return 'xcart_cidev_filter_values';
    }

    public static function getFields()
    {
        return [
            'fv_id' => [
                'class' => AutoField::className(),
                'primary' => true,
                'null' => false,
            ],
            'filter' => [
                'field' => 'f_id',
                'class' => ForeignField::className(),
                'modelClass' => FilterModel::className(),
                'link' => ['f_id' => 'f_id'],
                'null' => false,
                'default' => 0
            ],
            'fv_name' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'fv_order_by' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 10
            ],
            'fv_active' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 'Y'
            ],
        ];
    }
}
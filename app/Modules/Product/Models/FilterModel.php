<?php
namespace Modules\Product\Models;

use Modules\Sites\Models\SiteModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class FilterModel extends Model
{
    public static function tableName()
    {
        return 'xcart_cidev_filters';
    }

    public static function getFields()
    {
        return [
            'f_id' => [
                'class' => AutoField::className(),
                'primary' => true,
                'null' => false,
            ],
            'f_name' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'f_order_by' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 10
            ],
            'f_active' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 'Y'
            ],
            'storefrontid' => [
                'field' => 'storefrontid',
                'class' => ForeignField::className(),
                'modelClass' => SiteModel::className(),
                'link' => ['storefrontid' => 'storefrontid'],
                'null' => false,
                'default' => 0
            ],
        ];
    }
}
<?php
namespace Modules\Distributor\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;

class DistributorFeedFieldModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_manufacturer_feed_fields';
    }

    public static function getFields()
    {
        return [
            'feed_id' => [
                'class' => IntField::className(),
                'null' => true,
                'primary' => true,
                'default' => ''
            ],
            'manufacturerid' => [
                'class' => IntField::className(),
                'null' => true,
                'primary' => true,
                'default' => 0
            ],
            'field_name' => [
                'class' => CharField::className(),
                'null' => false,
                'primary' => true,
                'default' => ''
            ],
        ];
    }
}
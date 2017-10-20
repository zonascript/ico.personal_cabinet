<?php
namespace Modules\Distributor\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\SerializeField;

class SupplierFeedModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_supplier_feeds';
    }

    public static function getFields()
    {
        return [
            'feed_id' => [
                'class' => AutoField::className(),
                'primary' => true,
                'null' => false,
            ],
            'last_feed_fields' => [
                'class' => SerializeField::className(),
                'null' => false,
                'default' => ''
            ],
            'distributor' => [
                'field' => 'manufacturerid',
                'class' => ForeignField::className(),
                'modelClass' => DistributorModel::className(),
                'link' => ['manufacturerid' => 'manufacturerid'],
                'null' => false,
            ],
        ];
    }
}
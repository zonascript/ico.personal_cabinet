<?php
namespace Modules\Order\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Model;

class OrderAdditionalTagLinkModel extends Model
{
    public static function tableName()
    {
        return 'xcart_orders_additional_tags';
    }

    public static function getFields()
    {
        return [
            'id' => AutoField::className(),
//            'id' => [
//                ''
//            ],
            'status' => [
                'field' => 'status_id',
                'class' => ForeignField::className(),
                'modelClass' => AttentionTagModel::className(),
                'link' => ['status_id' => 'status_id'],
                'primary' => true,
            ],
            'order' => [
                'field' => 'orderid',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'link' => ['orderid' => 'orderid'],
                'primary' => true,
            ],
        ];
    }
}
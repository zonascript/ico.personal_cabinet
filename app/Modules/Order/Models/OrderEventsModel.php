<?php
namespace Modules\Order\Models;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\DateTimeField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Model;
use Xcart\Order;

/**
 * Class OrderEventsModel
 *
 * @package Modules\Order\Models
 *
 * @param int $orderid Owner id from order
 * @param \DateTime|string $create_at Owner id from order
 * @param string|null $message event message
 */
class OrderEventsModel extends Model
{
    public static function tableName()
    {
        return 'xcart_order_events';
    }

    public static function getFields()
    {
        return [
            'order' => [
                'field' => 'order_id',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'link' => ['order_id' => 'orderid'],
                'primary' => true,
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'primary' => true,
            ],
            'message' => [
                'class' => CharField::className(),
                'null' => true
            ]
        ];
    }

    /**
     * @param OrderModel|Order|null $owner Owner order model
     * @param int $order_id                Order id
     * @param string|null $message         Message for event
     *
     * @return null|static
     * @throws \Exception
     */
    public static function newOrderEvent($owner = null, $order_id, $message = null)
    {
        if (static::objects()->filter(['order_id' => $order_id, 'created_at' => new \DateTime()])->count() == 0)
        {
            $model = new static();
            $model->setAttributes(['order_id' => $order_id, 'message' => $message]);

            if ($model->isValid() && $model->save())
            {
                return $model;
            }
        }

        return null;
    }
}
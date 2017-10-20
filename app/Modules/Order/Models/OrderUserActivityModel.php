<?php
namespace Modules\Order\Models;

use Modules\User\Models\UserModel;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Fields\DateTimeField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Model;

class OrderUserActivityModel extends Model
{
    public static function tableName()
    {
        return 'xcart_order_user_actives';
    }

    public static function getFields()
    {
        return [
            'user' => [
                'field' => 'user_id',
                'class' => ForeignField::className(),
                'modelClass' => UserModel::className(),
                'link' => ['id', 'user_id'],
                'primary' => true,
            ],
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
                'autoNow' => true,
            ]
        ];
    }

    public function save(array $fields = [])
    {
        $filter = [
            'user_id'=> $this->user_id,
            'order_id' => $this->order_id,
            'created_at__gte' => (new \DateTime())->modify( '-2 minutes' )
        ];

        if (static::objects()->filter($filter)->count()) {
            static::objects()->filter($filter)->delete();

            $this->setIsNewRecord(true);
        }

        return parent::save($fields);
    }

    public function afterSave($owner, $isNew)
    {
        OrderUserLastActivityModel::objects()->updateOrCreate(['user_id'=> $this->user_id, 'order_id' => $this->order_id], ['created_at' => $this->created_at]);
    }

    public static function userView($owner = null, $order_id)
    {
        $user = Xcart::app()->user;
        if (!$user->getIsGuest() && $order_id) {
            (new static(['user_id' => $user->id, 'order_id' => $order_id]))->save();
        }
    }
}
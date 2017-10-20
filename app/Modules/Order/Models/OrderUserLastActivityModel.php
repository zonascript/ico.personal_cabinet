<?php
namespace Modules\Order\Models;

class OrderUserLastActivityModel extends OrderUserActivityModel
{
    public static function tableName()
    {
        return 'xcart_order_user_actives_last';
    }

    public function afterSave($owner, $isNew)
    {

    }
}
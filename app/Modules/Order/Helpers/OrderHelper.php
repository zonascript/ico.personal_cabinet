<?php
namespace Modules\Order\Helpers;

use DateTime;
use Mindy\QueryBuilder\Expression;
use Mindy\QueryBuilder\Q\QAnd;
use Mindy\QueryBuilder\Q\QOr;
use Mindy\QueryBuilder\QueryBuilder;
use Modules\Order\Models\OrderEventsModel;
use Modules\Order\Models\OrderGroupModel;
use Modules\Order\Models\OrderModel;
use Modules\Order\Models\OrderStatusModel;
use Modules\Order\Models\OrderUserLastActivityModel;
use Modules\User\Models\UserModel;
use Xcart\App\Main\Xcart;

class OrderHelper
{
    protected static $__events_count = [];
    protected static $__max_eta = [];

    public static function getMaxEtaTimeByOrder(array $ids)
    {
        $keys = array_keys(self::$__max_eta);
        $diff = array_diff($ids, $keys);

        if (!empty($diff)) {
            $connection = Xcart::app()->db->getConnection();
            $max_eta_sql = QueryBuilder::getInstance($connection)->from('xcart_products')
                                       ->select(['max_eta' => new Expression('MAX(t.eta_date_mm_dd_yyyy)'), 'details.orderid'])
                                       ->setAlias('t')
                                       ->join('inner join', 'xcart_order_details', ['t.productid' => 'details.productid'], 'details')
                                       ->where(['details.orderid__in' => $diff, 'eta_date_mm_dd_yyyy__gt' => 0])
                                       ->group(['details.orderid'])->toSQL();

            $orders_max_eta = $connection->fetchAll($max_eta_sql);

            foreach ($orders_max_eta as $item) {
                self::$__max_eta[$item['orderid']] = $item['max_eta'];
            }
        }

        $result = [];
        foreach (self::$__max_eta as $id => $eta) {
            if (in_array($id, $ids)) {

                $result[$id] = $eta;
            }
        }

        return $result;
    }


    public static function getCountEvents(array $ids, $user_id = null, $group = true)
    {
        $need_request = false;
        $userModel = null;

        if (empty($user_id) && Xcart::app()->getIsWebMode())
        {
            $userModel = Xcart::app()->user;
            $user_id = $userModel->id;
        }

        foreach ($ids as $id) {
            $need_request = !isset(self::$__events_count[$id]) || !isset(self::$__events_count[$id][$user_id]);

            if ($need_request) {
                break;
            }
        }

        if ($need_request && !$userModel) {
            $userModel = UserModel::objects()->get(['id' => $user_id]);
        }

        if ($need_request && $user_id && $userModel && $userModel->show_events) {

            $connection = Xcart::app()->db->getConnection();

            $min_date = ($userModel->show_events_min_date) ? (new DateTime($userModel->show_events_min_date)) : null;

            $qs = static::getEventCountQS($user_id, $min_date);
            $topAlias = $qs->getTableAlias();

            $sql = $qs->filter(['order_id__in' => $ids,])->group(["{$topAlias}.order_id"])->allSql();

            $counts = $connection->fetchAll($sql);
            if ($counts) {
                foreach ($counts as $item) {
                    self::$__events_count[$item['order_id']][$user_id] = $item['count'];
                }
            }
            foreach ($ids as $id) {
                if (empty(self::$__events_count[$id])) {
                    self::$__events_count[$id][$user_id] = 0;
                }
            }
        }

        $result = [];
        foreach (self::$__events_count as $id => $user_count) {
            if (in_array($id, $ids)) {

                $result[$id] = $user_count[$user_id];
            }
        }

        return ($group) ? $result : array_sum($result);
    }

    /**
     * Return QuerySet without order filtrate
     *
     * @param int $user_id
     * @param null|\DateTime $min_show_date Minimal date for show event
     *
     * @return \Xcart\App\Orm\Manager
     */
    public static function getEventCountQS($user_id, $min_show_date = null)
    {
        $qs = OrderEventsModel::objects();
        $topAlias = $qs->getTableAlias();

        if ($min_show_date && $min_show_date instanceof \DateTime) {
            $qs = $qs->filter(['created_at__gte' => $min_show_date]);
        }

        $qs = $qs
            ->filter([
                new QAnd(['created_at__gte' => (new \DateTime())->modify('-6 month'),]),
                new QOr([
                    new QAnd(['a.user_id' => $user_id, new QAnd(new Expression("`{$topAlias}`.`created_at` >= `a`.`created_at`"))]),
                    'a.user_id__isnull' => true
                ]),
            ])
            ->getQuerySet()
            ->join('left join', OrderUserLastActivityModel::tableName(), ['a.order_id' => 'order_id', 'a.user_id' => new Expression($user_id)], 'a')
            ->select(['order_id', 'count' => new Expression('count(*)')]);

        return $qs;
    }

    /**
     * Return Log string
     *
     * @param OrderModel $model
     * @param $status
     *
     * @return array
     */
    public static function changeOrderCBStatus($model, $status)
    {
        $log = null;
        $send = false;
        if ($model->groups) {
            /** @var OrderGroupModel $group */
            foreach ($model->groups as $group) {
                if (in_array($group->cb_status, ['Q', 'N', 'I'])) {
                    if ($group->cb_status != $status) {
                        $log = "<br/><b>" . $group->manufacturer->code . ":</b> cb_status: " . $group->status_cb->name
                            . " -> " . OrderStatusModel::objects()->get(['code' => $status])->name;
                    }
                    $send = true;
                    $group->cb_status = $status;
                    $group->save();
                }
            }
            if ($send && $model->cb_status != $status) {
                $model->cb_status = $status;
                $model->save();
            }
        }
        return [$log, $send];
    }
}
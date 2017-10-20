<?php

namespace Modules\Order\Helpers;


use Modules\Core\Models\GlobalConfigModel;
use Modules\Order\Models\OrderModel;
use Modules\Sites\Models\SiteConfigModel;
use TheIconic\Tracking\GoogleAnalytics\Analytics;
use Xcart\App\Main\Xcart;

class OrderAnalyticsHelper
{
    public static function sendRefund(OrderModel $model)
    {
        if ($model) {

            try {
                $analytics = new Analytics();

                if ($model->storefrontid) {
                    $UA = SiteConfigModel::objects()->get(['name' => 'cidev_ga_code_number', 'storefrontid' => $model->storefrontid])->value;
                } else {
                    $UA = GlobalConfigModel::objects()->get(['name' => 'cidev_ga_code_number'])->value;
                }

                $clientId = time() . "." . time();

                if (($ga_cookie = Xcart::app()->request->cookie->get('_ga')) && ($ga_cla = explode('.', $ga_cookie))) {
                    $clientId = "{$ga_cla[2]}.{$ga_cla[3]}";
                }

                $analytics
                    ->setTransactionId($model->getOrderNumber())
                    ->setProductActionToRefund()
                    ->setEventCategory('Ecommerce')
                    ->setEventAction('Refund')
                    ->setNonInteractionHit('1')
                    ->setProtocolVersion('1')
                    ->setTrackingId($UA)
                    ->setClientId($clientId)
                    ->sendEvent();
            }

            catch (\Exception $e) {
                func_log_order($model->orderid, 'X', 'GA Error: ' . $e->getMessage(), Xcart::app()->user->login);
            }
        }
    }
}
<?php

namespace Modules\Order\Helpers;


use Modules\Order\Models\OrderModel;
use Modules\Order\Models\OrderTransactionModel;
use Modules\Payment\Gateways\Gateway;
use Modules\Payment\Helpers\PaymentHelper;
use Modules\Payment\Models\PaymentMethodModel;

class OrderTransactionHelper
{

    /**
     * @param OrderTransactionModel $model
     * @param Gateway $gw
     * @param OrderModel $orderModel
     * @param PaymentMethodModel $pmModel
     * @param array $params
     * @param string $mode
     * @return OrderTransactionModel
     */
    public static function prepareOrderTransaction($model, $gw, $orderModel = null, $pmModel, $params = null, $mode = '')
    {
        if ((!$model && $orderModel) || (in_array($mode, ['refund_transaction']) && $model->transaction_status != OrderTransactionModel::STATUS_REFUNDED)) {
            $model = new OrderTransactionModel(['orderid' => $orderModel->orderid]);
        }
        if ($model) {

            $result = $gw->result->getData();

            if (!$result['amount']) {
                $result['amount'] =
                    [
                        'total' => $params['amount'],
                        'currency' => $params['currency']
                    ];
            }

            if ($mode == 'add_manual_transaction') {
                $model->manual_transaction = 'Y';
            }

            if ($mode == 'refund_transaction') {
                $result['amount']['total'] = -abs($result['amount']['total']);
            }

            if (isset($result['capture_id'])) {
                if ($parent = OrderTransactionModel::objects()->get(['transaction_id' => $result['capture_id']])){
                    $model->parent_id = $parent->id;
                }
            }

            $model->setAttributes(
                [
                    'transaction_id' => $gw->result->getTransactionReference(),
                    'transaction_status' => ($logStatus = $gw->getState($mode)),
                    'transaction_currency' => $result['amount']["currency"],
                    'transaction_amount' => $result['amount']['total'],
                    'transaction_response' => $result,
                    'paymentid' => $pmModel->paymentid,
                    'transaction_fee' => isset($result['transaction_fee']) ? $result['transaction_fee']['value'] : null,
                ]
            );
        }
        return $model;
    }

    /**
     * @param string $method
     * @param OrderTransactionModel $model
     * @param array $params
     */
    public static function action($method, $model, $params)
    {
        if ($model) {
            if ($gw = Gateway::getGateway($model->payment_method_model->processor)) {
                if ($res = $gw->$method($params)) {
                    $model = OrderTransactionHelper::prepareOrderTransaction($model, $gw, null, $model->payment_method_model, $params);
                }
            }
        }
        return $model;
    }

    public static function getOrderTransactionsGroupsValues(OrderModel $order)
    {
        $trs = [];
        if ($order) {
            foreach ($order->transactions as $transaction) {
                $trs[strtolower($transaction->transaction_status)] += $transaction->transaction_amount;
            }
        }

        return [
            'authorized_PLUS_captured_totals' => floatval(
                $trs[OrderTransactionModel::STATUS_COMPLETED]
                + $trs[OrderTransactionModel::STATUS_AUTHORIZED]
                + $trs[OrderTransactionModel::STATUS_PENDING]
                + $trs[OrderTransactionModel::STATUS_PARTIALLY_RUFUNDED]
            ),
            'void_total' => floatval($trs[OrderTransactionModel::STATUS_VOIDED]),
            'authorized_total' => floatval($trs[OrderTransactionModel::STATUS_AUTHORIZED] + $trs[OrderTransactionModel::STATUS_PENDING]),
            'captured_total' => floatval($trs[OrderTransactionModel::STATUS_COMPLETED] + $trs[OrderTransactionModel::STATUS_PARTIALLY_RUFUNDED])
        ];
    }
}
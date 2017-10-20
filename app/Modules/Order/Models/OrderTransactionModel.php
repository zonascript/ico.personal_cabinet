<?php
namespace Modules\Order\Models;

use Modules\Payment\Models\PaymentMethodModel;
use Modules\User\Models\UserModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\SerializeField;
use Xcart\App\Orm\Fields\UnixTimestampField;

class OrderTransactionModel extends AutoMetaModel
{
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PENDING = 'pending';
    const STATUS_VOIDED = 'voided';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PARTIALLY_RUFUNDED = 'partially_refunded';

    public static function tableName()
    {
        return 'xcart_order_transactions';
    }

    public static function getFields()
    {
        return [
            'id' => [
                'class' => AutoField::className(),
            ],
            'transaction_status'  => [
                'class' => CharField::className(),
                'default' => 'failed',
                'null' => false,
                'choices' => [
                    'AP' => 'Authorized',
                    'pending' => 'Pending',
                    'authorized' => 'Authorized',
                    'voided' => 'Voided',
                    'completed' => 'Completed',
                    'expired' => 'Expired',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                    'partially_refunded' => 'Partially Refunded',
                ]
            ],
            'transaction_response' => [
                'class' => SerializeField::className(),
                'null' => true,
            ],
            'payment_method_model' => [
                'field' => 'paymentid',
                'class' => ForeignField::className(),
                'modelClass' => PaymentMethodModel::className(),
                'null' => false,
            ],
            'date' => [
                'class' => UnixTimestampField::className(),
                'autoNowAdd' => true,
                'autoNow' => true,
            ],
            'user' => [
                'field' => 'login',
                'class' => ForeignField::className(),
                'modelClass' => UserModel::className(),
                'link' => ['login' => 'login'],
            ],
            'transaction_logs' => [
                'class' => HasManyField::className(),
                'modelClass' => TransactionLogModel::className(),
                'link' => ['order_transaction_id' => 'id'],
            ],
        ];
    }
}
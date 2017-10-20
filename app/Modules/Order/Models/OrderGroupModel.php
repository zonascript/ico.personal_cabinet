<?php
namespace Modules\Order\Models;

use Modules\Distributor\Models\DistributorModel;
use Modules\Product\Models\ProductModel;
use Modules\Shipping\Models\ShippingModel;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\SerializeField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\OrderGroup;

class OrderGroupModel extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return OrderGroup::className();
    }

    public static function tableName()
    {
        return 'xcart_order_groups';
    }

    public static function getFields()
    {
        return [
            'order' => [
                'field' => 'orderid',
                'class' => ForeignField::className(),
                'modelClass' => OrderModel::className(),
                'null' => false,
                'primary' => true,
            ],
            'manufacturer' => [
                'field' => 'manufacturerid',
                'class' => ForeignField::className(),
                'modelClass' => DistributorModel::className(),
                'null' => false,
                'primary' => true,
            ],
            'shippingModel' => [
                'field' => 'shippingid',
                'class' => ForeignField::className(),
                'modelClass' => ShippingModel::className(),
                'null' => false,
            ],
            'status_cb' => [
                'field' => 'cb_status',
                'class' => ForeignField::className(),
                'modelClass' => OrderStatusModel::className(),
                'null' => false,
            ],
            'status_dc' => [
                'field' => 'cb_status',
                'class' => ForeignField::className(),
                'modelClass' => OrderStatusModel::className(),
                'null' => false,
            ],
            'invoices' => [
                'class' => HasManyField::className(),
                'modelClass' => OrderGroupInvoiceModel::className(),
                'link' => ['orderid'=>'orderid', 'manufacturerid'=>'manufacturerid'],
            ],
            'memos' => [
                'class' => HasManyField::className(),
                'modelClass' => OrderGroupMemoModel::className(),
                'link' => ['orderid'=>'orderid', 'manufacturerid'=>'manufacturerid'],
            ],
            'tracking' => [
                'class' => SerializeField::className(),
                'null' => false,
                'default' => '',
            ],
            'accounting' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'manufacturer_data' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'OLD_accounting' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'amz_customer_notes' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],

        ];
    }

    private static $shippingModels = [];
    public function getShippingModel()
    {
        if (isset(self::$shippingModels[$this->shippingid])) {
            $this->shippingModel = self::$shippingModels[$this->shippingid];
            return self::$shippingModels[$this->shippingid];
        }

        self::$shippingModels[$this->shippingid] = $this->shippingModel;
        return self::$shippingModels[$this->shippingid];
    }

    public function getPaymentMethodId()
    {
        return $this->acc_paymentid;
    }

    /**
     * @param OrderGroup $model
     */
    public function afterFetchDataModel($model)
    {

    }

    private $productModels = null;
    public function getProductModels()
    {
        if (is_null($this->productModels)) {
            $this->productModels = ProductModel::objects()
                ->getQuerySet()
                ->join('inner join', 'xcart_order_details', ['productid' => 'od.productid'], 'od')
                ->filter(['manufacturerid' => $this->manufacturerid, 'od.orderid' => $this->orderid])
                ->all();
        }
        return $this->productModels;
    }

    private $detailsModels = null;
    public function getOrderDetailModels()
    {
        if (is_null($this->detailsModels)) {
            $this->detailsModels = OrderDetailModel::objects()
                ->getQuerySet()
                ->join('inner join', 'xcart_products', ['productid' => 'p.productid'], 'p')
                ->filter(['p.manufacturerid' => $this->manufacturerid, 'orderid' => $this->orderid])
                ->all();
        }
        return $this->detailsModels;
    }
}
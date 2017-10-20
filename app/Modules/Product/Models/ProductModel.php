<?php
namespace Modules\Product\Models;

use Modules\Brand\Models\BrandModel;
use Modules\Menu\Models\CleanUrlModel;
use Modules\Sites\Models\SiteModel;
use Xcart\App\Components\Breadcrumbs;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\ManyToManyField;
use Xcart\App\Traits\DataModelTrait;
use Xcart\Product;

//use Xcart\Product;

/**
 * @property string forsale
 * @property string update_search_index
 * @property string productcode
 * @property mixed eta_date_mm_dd_yyyy
 * @property mixed eta_date_lock
 * @property mixed productid
 * @property mixed distributor
 * @property mixed|null dim_x
 * @property mixed|null dim_y
 * @property mixed|null dim_z
 * @property mixed shipping_weight_lock
 * @property mixed|null shipping_weight
 * @property mixed|null weight
 * @property mixed weight_lock
 * @property mixed dim_lock
 * @property mixed shipping_dim_lock
 * @property mixed|null shipping_dim_x
 * @property mixed|null shipping_dim_y
 * @property mixed|null shipping_dim_z
 * @property mixed product
 * @property mixed fulldescr
 * @property string controlled_by_feed
 * @property mixed brandid
 * @property integer source_sfid
 * @property integer manufacturerid
 * @property int add_date
 * @property int mod_date
 * @property mixed|string upc
 */
//class ProductModel extends AutoMetaModel implements ICartItem
class ProductModel extends AutoMetaModel
{
    use DataModelTrait;

    public static function getDataModelClass()
    {
        return Product::className();
    }

    public static function tableName()
    {
        return 'xcart_products';
    }

    public static function getFields()
    {
        return [
            'categories' => [
                'class' => ManyToManyField::className(),
                'modelClass' => CategoryModel::className(),
                'through' => ProductCategoriesModel::className(),
            ],

            'prices' => [
                'class' => HasManyField::className(),
                'modelClass' => PricingModel::className(),
                'link' => ['productid' => 'productid']
            ],

            'sites' => [
                'class' => ManyToManyField::className(),
                'modelClass' => SiteModel::className(),
                'through' => ProductStorefrontModel::className(),
            ],

            'quick_prices' => [
                'class' => ManyToManyField::className(),
                'modelClass' => PricingModel::className(),
                'through' => QuickPricingModel::className(),
            ],

            'url' => [
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => CleanUrlModel::className(),
                'link' => ['productid' => 'resource_id'],
                'extra' => ['resource_type' => 'P']
            ],


            'productid' => [
                'class' => AutoField::className(),
            ],
//            'distributor' => [
//                'field' => 'manufacturerid',
//                'class' => ForeignField::className(),
//                'modelClass' => DistributorModel::className(),
//                'link' => ['manufacturerid' => 'manufacturerid'],
//                'null' => false,
//            ],
            'brand' => [
                'field' => 'brandid',
                'class' => ForeignField::className(),
                'modelClass' => BrandModel::className(),
                'link' => ['brandid' => 'brandid'],
            ],
            'filter_values' => [
                'class' => ManyToManyField::className(),
                'modelClass' => FilterValueModel::className(),
                'through' => FilterProductModel::className(),
            ],
            'images' => [
                'class' => HasManyField::className(),
                'modelClass' => ImagePModel::className(),
                'link' => ['id' => 'productid'],
//                'extra' => ['avail' => 'Y']
            ],

            'descr' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'fulldescr' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'seo_fulldescr' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
            'source_sfid' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
            'clone_parent_productid' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
        ];
    }

    public function getMPN()
    {
        $sMPN = null;
        $model = $this->distributor;
        if (strpos($this->productcode, $model->code) == 0) {
            $sMPN = preg_replace("/^(" . $model->code . "-)/i", "", $this->productcode);
        }
        return $sMPN;
    }


    public function getParamList()
    {
        if ($values = $this->filter_values->filter(['fv_active' => 'Y'])->order(['f_id','fv_order_by'])->cache(30)->all()) {

            $filters = FilterModel::objects()->filter(['f_id__in' => array_map(function($value){ return $value->f_id; }, $values)])->order(['f_order_by'])->cache(30)->all();

            $list = [];
            foreach ($filters as $filter)
            {
                $list[$filter->f_id] = ['name' =>$filter->f_name, 'values' => []];
            }
            
            foreach ($values as $value)
            {
                if ($list[$value->f_id]) {
                    $list[$value->f_id]['values'][] = $value->fv_name;
                }
            }

            return $list;
        }

        return false;
    }

    public function isNewProduct()
    {
        $sInDay = (60 * 60 * 24);

        return ($this->add_date + $sInDay * 30)  >= time();
    }

    public function isSaleSticker()
    {
        $fp = $this->getFrontendPrice();

        return ($this->list_price > ($fp + $fp * .3));
    }


    public function isOutOfStock()
    {
        return $this->isProductOutOfStock();
    }

    public function getAbsoluteUrl($full = false)
    {
        $path = '';

        if ($full) {
            if ($site = $this->sites->limit(1)->get()) {
                $path .= $site->domain . '/';
            }
        }

        if ($this->url) {
            $path .= $this->url->clean_url;
        }
        else {
            $path = Xcart::app()->router->url('catalog:product:view', ['id' => $this->productid, 'slug' => $this->product]);
        }


        if ($full) {
            $path = '//' . $path;
        }
        else {
            $path = '/' . $path;
        }

        return $path;

//        return Xcart::app()->router->url('catalog:product:view', ['sku' => $this->productcode]);
    }
    public function getBreadcrumbs()
    {
        /** @var CategoryModel $category */
        if ($category = CategoryModel::objects()->filter(['products__through__main' => 'Y', 'products_link__productid' => $this->productid])->limit(1)->get()) {
            $bread = $category->getBreadcrumbs();
        }
        else {
            $bread = new Breadcrumbs();
        }

        $bread->add($this->product, $this->getAbsoluteUrl());

        return $bread;
    }

    public function getPrice($quantity = 1)
    {
        return $this->getDataModel()->getPrice($quantity);
    }

    public function recalculate($quantity, $type, $data)
    {
        return $quantity * $this->getPrice($quantity);
    }

    public function getUniqueId($data = [])
    {
        return $this->productid;
    }

    public function __toString()
    {
        return "[{$this->productid}] {$this->product} ({$this->productcode})";
    }

    public function getFrontendName()
    {
        return $this->seo_product_name ?: $this->product;
    }

    public function getFrontendDescription()
    {
        return $this->descr ?: $this->seo_fulldescr ?: $this->fulldescr;
    }
}
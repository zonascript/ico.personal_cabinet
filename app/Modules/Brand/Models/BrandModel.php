<?php

namespace Modules\Brand\Models;

use Modules\Brand\BrandModule;
use Modules\Menu\Models\CleanUrlModel;
use Modules\Product\Models\ProductModel;
use Modules\Sites\Models\SiteModel;
use Modules\User\Models\UserModel;
use Xcart\App\Components\Breadcrumbs;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
//use Xcart\App\Orm\Fields\BooleanCharField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\ManyToManyField;

/**
 * @property mixed brandid
 */
class BrandModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_brands';
    }

    public static function getFields()
    {
        return [
            'brandid' => [
                'class' => AutoField::className(),
                'primary' => true,
                'null' => false,
            ],
            'descr' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t('Description')
            ],
            'brand' => [
                'class' => CharField::className(),
                'null' => false,
                'verboseName' => BrandModule::t('Brand'),
            ],
            'meta_descr' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t('SEO meta description')
            ],
//            'avail' => [
//                'class' => BooleanCharField::className(),
//                'null' => false,
//                'default' => 'Y',
//                'verboseName' => BrandModule::t('Availability')
//            ],
//            'prevent_search_indexing_of_all_brand_products' => [
//                'class' => BooleanCharField::className(),
//                'null' => false,
//                'default' => 'N',
//                'verboseName' => BrandModule::t('Prevent search indexing of all brand products')
//            ],
//            'prevent_search_indexing_brand_page' => [
//                'class' => BooleanCharField::className(),
//                'null' => false,
//                'default' => 'N',
//                'verboseName' => BrandModule::t('Prevent search indexing brand page')
//            ],
            'disclaimer_text' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t('Brand disclaimer')
            ],
            'title' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t("Title (<title>)")
            ],
            'SEO_brand_name_h1' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t("SEO brand name (<H1>)")
            ],
            'SEO_h2' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'verboseName' => BrandModule::t("SEO (<H2>)")
            ],
            'brand_storefront' => [
                'class' => HasManyField::className(),
                'modelClass' => BrandStorefrontModel::className(),
                'link' => ['brandid' => 'brandid']
            ],
            'storefront' => [
                'class' => ManyToManyField::className(),
                'modelClass' => SiteModel::className(),
                'through' => BrandStorefrontModel::className(),
            ],
            'child_brands' => [
                'class' => HasManyField::className(),
                'modelClass' => BrandModel::className(),
                'link' => ['parent_brand_id' => 'brandid']
            ],
            'products' => [
                'class' => HasManyField::className(),
                'modelClass' => ProductModel::className(),
                'link' => ['brandid' => 'brandid']
            ],
            'parent' => [
                'field' => 'parent_brand_id',
                'class' => ForeignField::className(),
                'modelClass' => BrandModel::className(),
                'link' => ['parent_brand_id' => 'brandid']
            ],
            'user' => [
                'field' => 'provider',
                'class' => ForeignField::className(),
                'modelClass' => UserModel::className(),
                'link' => ['provider' => 'login']
            ],

            'url' => [ //@TODO: TEMPORARY
                'field' => 'productid',
                'class' => ForeignField::className(),
                'modelClass' => CleanUrlModel::className(),
                'link' => ['brandid' => 'resource_id'],
                'extra' => ['resource_type' => 'M']
            ],

        ];
    }

    public function getImage()
    {
        return ImageBModel::objects()->limit(1)->get(['id' => $this->brandid]);
    }

    public function getBreadcrumbs()
    {
        $bread = new Breadcrumbs();

        $bread->add('Brands', 'brand:list');
        $bread->add($this->brand, $this->getAbsoluteUrl());
        return $bread;
    }

    public function getAbsoluteUrl($full = false)
    {
        if ($this->brandid)
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
                return false;
            }


            if ($full) {
                $path = '//' . $path;
            }
            else {
                $path = '/' . $path;
            }

            return $path;

//            return Xcart::app()->router->url('brand:view', ['id' => $this->brandid, 'slug' => 'TEMP']);
        }

        return false;
    }

    public function getAdminUrl()
    {
        if ($this->isNewRecord) {
            return Xcart::app()->router->url('brand:create_brand');
        } else {
            return Xcart::app()->router->url('brand:update_brand', ['id' => $this->brandid]);
        }
    }

    public function getUrl()
    {
        /** TODO rewrite on new router */
        return "/brand/{$this->brandid}";
    }

}
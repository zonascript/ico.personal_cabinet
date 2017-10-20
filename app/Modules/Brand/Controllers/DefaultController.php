<?php
namespace Modules\Brand\Controllers;

use Mindy\QueryBuilder\Expression;
use Modules\Brand\Models\BrandModel;
use Modules\Product\Controllers\AbstractCatalogController;
use Modules\Product\Models\CategoryModel;
use Modules\Product\Models\ProductModel;
use Xcart\App\Components\Breadcrumbs;
use Xcart\App\Main\Xcart;

class DefaultController extends AbstractCatalogController
{
    public $view = 'brand/view.tpl';
    public $filters = ['price', 'filter'];

    public function actionViewOld($id, $slug)
    {
        $this->view_internal(BrandModel::objects()->filter(['brandid' => $id])->get());
    }

    public function actionView($sku)
    {
//        $this->view_internal(BrandModel::objects()->filter(['productcode' => $sku])->get());
    }

    public function actionToList()
    {
        $this->redirect('brand:list', [], 301);
    }

    public function actionList()
    {
        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->add('Brands', 'brand:list');

        echo $this->render('brand/list.tpl', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    public function getAdvancedData($data = null)
    {
        /** @var \Modules\Sites\SitesModule $siteModule */
        $siteModule = Xcart::app()->getModule('Sites');
        $qs = CategoryModel::objects()->filter([
            'categoryid__in' => $this->getQS($data)->select(['categories__categoryid']),
            'storefrontid' => $siteModule->getSite()->storefrontid
        ]);

        $ta = $qs->getTableAlias();

        $pcountSql = $this->getQS($data)
            ->with(['categories'])
            ->filter([
                'categories__lft__gte' => new Expression("{{category}}.lft"),
                'categories__rgt__lte' => new Expression("{{category}}.rgt"),
                'categories__root' => new Expression("{{category}}.root"),
            ])
            ->countSql();

        $pcountSql = str_replace($ta, 'cp', $pcountSql);
        $pcountSql = str_replace("{{category}}", $ta, $pcountSql);

        $qs->group(['categoryid'])
           ->select(['pcount' => $pcountSql, '*', ]);
        $categories = $qs->cache(300)->order(['-pcount', 'category'])->all();

        return [
            'categories' => $categories ? : [],
        ];
    }

    public function getQS($data)
    {
        /** @var \Modules\Sites\SitesModule $siteModule */
        $siteModule = Xcart::app()->getModule('Sites');
        return ProductModel::objects()->filter([
            'forsale' => 'Y',
            'brand__brandid' => $data->brandid,
            'sites__storefrontid' => $siteModule->getSite()->storefrontid
        ]);
    }
}
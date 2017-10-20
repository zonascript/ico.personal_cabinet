<?php
namespace Modules\Main\Controllers;

use Fenom\Modifier;
use Mindy\QueryBuilder\Expression;
use Modules\Main\MainModule;
use Modules\Product\Models\ProductModel;
use Modules\Product\Controllers\SearchController as ProductSearchController;
use Xcart\App\Controller\FrontendController;
use Xcart\App\Main\Xcart;

class SearchController extends FrontendController
{
    public $defaultAction= 'index';
    /** @var ProductSearchController $p_controller  */
    public $p_controller;
    public $products_per_page = 20;

    public function beforeAction($action, $params)
    {
        $this->p_controller = new ProductSearchController($this->getRequest());

        parent::beforeAction($action, $params);
    }

    public function index()
    {
        $this->addTitle(MainModule::t('Shop search', [] , 'search'));
        echo $this->render('main/search.tpl', [
            'q' => $this->getRequest()->get->get('q', ''),
            'products_per_page' => $this->products_per_page
        ]);
    }

    public function actionApiSearch()
    {
        if ($this->getRequest()->getIsAjax()) {
            if (Xcart::app()->request->get->get('q')) {
                $q = Xcart::app()->request->get->get('q');
                $page = Xcart::app()->request->get->get('p', 1);
                $count = 0;

                $qs = ProductModel::objects()->filter(['forsale' => 'Y']);

                $products = $ids = [];

                if (preg_match('/^([a-z0-9]{3,4}-).++/i', $q)) {
                    $tqs = clone $qs;
                    $tqs->filter(['productcode__contains' => $q]);
                    $count = $tqs->count();

                    if ($count && $count == 1) {
                        /** @var ProductModel $product */
                        $product = $tqs->get();
                        $this->redirect($product->getAbsoluteUrl(true));
                    }
                    else if ($count) {
                        $products = $tqs->paginate($page, $this->products_per_page)->all();
                    }
                }

                if (!$products) {
                    if ($count = $this->p_controller->getProductFromElastic($q, null, $this->products_per_page, $page)) {
                        $ids = $this->p_controller->ids;

                        $qs = $qs->filter(['productid__in' => $ids]);
                        $qs->order([ new Expression("FIELD({$qs->getTableAlias()}.productid, " . implode(',', $ids) . ") ASC"), ]);
                        $products = $qs->all();
                    }
                }

                if ($products) {
                    $pa = [];
                    foreach ($products as $product) {
                        $pa[] = [
                            'id' => $product->productid,
                            'html' => $this->strip($this->render('catalog/parts/_catalog_list_item.tpl', [
                                'item' => $product,
                            ]), true, true),
                        ];
                    }

                    $this->jsonResponse([
                        'status' => 'ok',
                        'count' => $count,
                        'page' => $page,
                        'products' => $pa,
                    ]);
                    die();
                }
            }

            $this->jsonResponse(['status' => 'nothing']);
        }
    }

    //@TODO: Добавить как модификатор
    private function strip($str, $to_line = false, $hard = false)
    {
        $str = Modifier::strip($str, false);

        if ($hard) {
            $str = preg_replace('/>(([\0\s]++)(.*?)([\0\s]*+))<([\/\w]++)/', ">$3<$5", $str);
        }

        return $str;
    }
}
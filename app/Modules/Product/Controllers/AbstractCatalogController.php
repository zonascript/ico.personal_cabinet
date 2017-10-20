<?php

namespace Modules\Product\Controllers;

use Modules\Product\Helpers\ProductFilterHelper;
use Modules\Product\Helpers\ProductSortHelper;
use Modules\Product\Models\CategoryModel;
use Modules\Product\Models\ProductModel;
use Xcart\App\Controller\FrontendController;
use Xcart\App\Main\Xcart;
use Xcart\App\Pagination\DataSource\QuerySetDataSource;
use Xcart\App\Pagination\Pagination;

abstract class AbstractCatalogController extends FrontendController
{
    public $view = '';
    public $model = null;
    public $sort = null;
    public $pageSize = 40;
    public $filters = ['price', 'brand', 'filter'];

    public function getAdvancedCacheData()
    {
        return ['category_sort' => Xcart::app()->request->session->get('category_sort', ProductSortHelper::$default)];
    }

    public function beforeAction($action, $params)
    {
        if ( $this->getRequest()->getIsPost() && !empty($_POST['sort'])) {
            $this->getRequest()->session->add('category_sort', $_POST['sort']);
            echo "OK";
            Xcart::app()->end();
        }

        $this->sort = Xcart::app()->request->session->get('category_sort', ProductSortHelper::$default);

        parent::beforeAction($action, $params);
    }


    /**
     * @param mixed $data
     *
     * @return \Xcart\App\Orm\QuerySet|\Xcart\App\Orm\Manager
     */
    public function getQS($data)
    {
        return ProductModel::objects()->filter([ 'forsale' => 'Y' ]);
    }

    /**
     * @param \Xcart\App\Orm\QuerySet $qs
     * @param CategoryModel|[] $qs
     *
     * @return \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet
     */
    public function getSortedQS($qs, $model = null)
    {
        return (new ProductSortHelper($qs))
            ->setCategory(($model instanceof CategoryModel ? $model : null))
            ->getSortedQS($this->sort);
    }

    /**
     * @param \Xcart\App\Orm\QuerySet $qs
     *
     * @return \Xcart\App\Pagination\Pagination
     */
    public function getPager($qs)
    {
        return new Pagination($qs, ['pageSize' => $this->pageSize, 'view' => 'core/pager/front_endless.tpl', 'pageKey' => 'page'], new QuerySetDataSource());
    }

    /**
     * @param $data
     *
     * @return \Xcart\App\Components\Breadcrumbs|array|null
     */
    public function getBreadcrumbsFromData($data)
    {
        return $data->getBreadcrumbs();
    }

    public function getAdvancedData($data = null) { return []; }

    /**
     * @param CategoryModel|null $model
     *
     * @throws \Exception
     * @throws \Xcart\App\Exceptions\HttpException
     */
    protected function view_internal($model = null)
    {
        $this->model = $model;

        $orderBy = Xcart::app()->request->session->get('category_sort', ProductSortHelper::$default);

        /** @var \Xcart\App\Orm\QuerySet $pqs */
        $pqs = $this->getQS($model);

        $fh = new ProductFilterHelper($pqs, $this->getRequest()->get->get('filter', []), $this->filters);

        $pqs = $fh->getFiltrateQS();

        $pqs = $this->getSortedQS($pqs);
        $pager = $this->getPager($pqs);

        if ($this->getRequest()->getIsAjax())
        {
            $pagerView = $pager->createView();

            $this->jsonResponse([
                'href' => $pagerView->hasNextPage() ? $pagerView->getUrl($pagerView->getPage() + 1) : false,
                'content' => $this->render($this->view, array_replace([ 'model' => $model, 'pager' => $pager,], $this->getAdvancedData($model))),
                'page_count' => $this->render('catalog/parts/_page_count.tpl', [ 'model' => $model, 'pager' => $pager,]),
            ]);
        }
        else {
            echo $this->render($this->view, array_replace([
                'model' => $model,
                'pager' => $pager,
                'sort'  => $orderBy,
                'sort_arr'  => ProductSortHelper::$orderBy,
                'breadcrumbs' => $this->getBreadcrumbsFromData($model),
                'filters' => $fh->getFilterStructure($this->filters),
            ], $this->getAdvancedData($model)));
        }
    }
}
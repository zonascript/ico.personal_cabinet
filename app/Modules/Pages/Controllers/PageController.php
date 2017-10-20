<?php

namespace Modules\Pages\Controllers;

use Modules\Pages\Models\Page;
use Xcart\App\Controller\FrontendController;
use Xcart\App\Main\Xcart;
use Xcart\App\Pagination\DataSource\QuerySetDataSource;
use Xcart\App\Pagination\Pagination;

/**
 * Class PageController
 * @package Modules\Pages
 */
class PageController extends FrontendController
{
    public $defaultAction = 'view';

    /**
     * @param Page $model
     * @return string
     */
    protected function getView(Page $model)
    {
        return "pages/" . $model->getView();
    }

    public function actionView($url = null)
    {
        $model = Page::objects()
            ->published()
            ->get(empty($url) ? ['is_index' => true] : ['url' => ltrim($url, '/')]);

        if ($model === null) {
            $this->error(404);
        }

        // Remove duplicate of index page
        if ($model->is_index && !empty($url)) {
            $this->error(404);
        }

        $this->setCanonical($model);
        $this->fetchBreadrumbs($model);

        echo $this->actionInternal($model);
    }

    protected function fetchBreadrumbs(Page $model)
    {
        if (!$model->is_index) {
            /** @var Page[] $pages */
            $pages = $model->tree()->ancestors()->order(['level'])->all();
            foreach ($pages as $page) {
                $this->addTitle($page->name);
                $this->addBreadcrumb($page->name, $page->getAbsoluteUrl());
            }
            $this->addTitle($model->name);
            $this->addBreadcrumb($model->name, $model->getAbsoluteUrl());
        }
    }

    public function actionInternal(Page $model)
    {
        $pager = new Pagination($model->getChildrenQuerySet(), [], new QuerySetDataSource());
        return $this->render($this->getView($model), [
            'model' => $model,
            'pager' => $pager,
        ]);
    }
}

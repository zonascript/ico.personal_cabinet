<?php

namespace Modules\Admin\Controllers;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Pagination\Pagination;
use Modules\Admin\Components\ModelAdmin;
use Modules\Core\Controllers\BackendController;
use Modules\Core\Models\UserLog;
use Modules\Core\Tables\UserLogTable;

class AdminController extends BackendController
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $module = $this->getModule();
        $this->addBreadcrumb($module->t('Dashboard'));
        $this->addTitle($module->t('Dashboard'));

        $dashboards = [];
        $dashboardClasses = $this->getModule()->getDashboardClasses();
        foreach ($dashboardClasses as $cls) {
            $dashboards[] = new $cls;
        }

        echo $this->render('admin/index.html', [
            'dashboards' => $dashboards
        ]);
    }

    /**
     * @param $module
     * @param $adminClass
     */
    public function actionList($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        /** @var \Modules\Admin\Components\ModelAdmin $admin */
        $admin = new $className(['moduleName' => $module]);
        if (is_string($admin->getModel()) && class_exists($admin->getModel()) === false) {
            $this->error(404);
        }

        if ($this->getRequest()->getIsPost() && isset($_POST['action'])) {
            $action = $_POST['action'];
            unset($_POST['action']);
            $admin->$action($_POST);
        }

        $params = isset($_POST['search']) ? array_merge([
            'search' => $_POST['search']
        ], $_GET) : $_GET;
        $admin->setParams($params);

        $moduleName = $admin->getModule()->getId();

        $context = $admin->index();
        $out = $this->render($admin->indexTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));

        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        if ($this->getRequest()->isAjax) {
            echo $out;
        } else {
            echo $this->render('admin/admin/list.html', array_merge(['adminClass' => $adminClass], [
                'module' => $admin->getModule(),
                'moduleName' => $module,
                'modelClass' => $admin->getModel(),
                'out' => $out,
                'admin' => $admin,
                'id' => isset($_GET['id']) ? $_GET['id'] : null
            ]));
        }
    }

    /**
     * @param $module
     * @param $adminClass
     * @return null|string
     */
    protected function getAdminClassName($module, $adminClass)
    {
        $className = "\\Modules\\" . $module . "\\Admin\\" . $adminClass;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }

    /**
     * @param $module
     * @param $adminClass
     * @param $id
     */
    public function actionInfo($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        $admin = new $className();
        $moduleName = $admin->getModel()->getModuleName();
        $context = $admin->info($id, $_GET);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->infoTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));
    }

    /**
     * @param $module
     * @param $adminClass
     * @param $id
     */
    public function actionInfoPrint($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        $admin = new $className();
        $moduleName = $admin->getModel()->getModuleName();
        $context = $admin->info($id, $_GET);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->infoPrintTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));
    }

    /**
     * @param $admin
     * @return array
     */
    protected function processAjaxSelect($admin)
    {
        $json = [];
        if (isset($_GET['select2'])) {
            $q = $_GET['select2'];
            $field = $_GET['field'];
            $modelField = $_GET['modelField'];

            $sourceModel = $admin->getModel();
            if (!$sourceModel->hasField($field)) {
                return [
                    'items' => [],
                    'total_count' => []
                ];
            }

            $cls = $sourceModel->getField($field)->modelClass;
            $model = new $cls;

            $qs = $model->objects()->filter([$modelField . '__startswith' => $q]);
            $total = $qs->count();

            $pager = new Pagination($qs, [
                'pageSize' => isset($_GET['pageSize']) ? $_GET['pageSize'] : 10
            ]);
            $pager->setPage(isset($_GET['page']) ? (int)$_GET['page'] : 1);

            $models = [];
            foreach ($pager->paginate() as $model) {
                $models[] = [
                    'text' => (string)$model,
                    'id' => $model->pk
                ];
            }
            return [
                'items' => $models,
                'total_count' => $total
            ];
        }
        return $json;
    }

    /**
     * @param $module
     * @param $adminClass
     */
    public function actionCreate($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        /** @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin $admin */
        $admin = new $className();

        $json = $this->processAjaxSelect($admin);
        if (!empty($json)) {
            echo $this->json($json);
            Mindy::app()->end();
        }

        $context = $admin->create($_POST, $_FILES);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->createTemplate, array_merge([
            'module' => $module,
            'adminClass' => $adminClass
        ], $context));
    }

    /**
     * @param $module
     * @param $adminClass
     * @param $id
     */
    public function actionUpdate($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        /** @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin $admin */
        $admin = new $className();

        $json = $this->processAjaxSelect($admin);
        if (!empty($json)) {
            echo $this->json($json);
            Mindy::app()->end();
        }

        $context = $admin->update($id, $_POST, $_FILES);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->updateTemplate, array_merge([
            'module' => $module,
            'adminClass' => $adminClass,
            'id' => $id
        ], $context));
    }

    /**
     * @param $module
     * @param $adminClass
     * @param $id
     */
    public function actionDelete($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        $admin = new $className();
        $admin->delete($id);
        $this->getRequest()->redirect(Mindy::app()->urlManager->reverse('admin:list', [
            'module' => $module,
            'adminClass' => $adminClass
        ]));
    }

    /**
     * @param Module $module
     * @return array
     */
    protected function menuToBreadcrumbs(Module $module)
    {
        $menu = $module->getMenu();
        $breadcrumbs = [];
        foreach ($menu['items'] as $menu) {
            $breadcrumbs[] = [
                'name' => $menu['name'],
                'url' => Mindy::app()->urlManager->reverse('admin:list', [
                    'module' => $module->getId(),
                    'adminClass' => $menu['adminClass']
                ])
            ];
        }
        return $breadcrumbs;
    }

    /**
     * @param array $breadcrumbs
     * @param ModelAdmin $admin
     * @return array
     */
    public function formatBreadcrumbs(array $breadcrumbs, ModelAdmin $admin)
    {
        foreach ($breadcrumbs as $i => &$item) {
            if ($i == 1) {
                $item['items'] = $this->menuToBreadcrumbs($admin->getModule());
                break;
            }
            continue;
        }

        return $breadcrumbs;
    }

    /**
     * @param $breadcrumbs
     */
    protected function convertBreadcrumbsToTitle($breadcrumbs)
    {
        foreach ($breadcrumbs as $bc) {
            $this->addTitle($bc['name']);
        }
    }
}

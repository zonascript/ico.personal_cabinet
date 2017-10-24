<?php

namespace Modules\Admin\Components;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Pagination\Pagination;
use Modules\Admin\Tables\AdminTable;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 19/03/14.03.2014 18:40
 */
abstract class NestedAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    public $nestedColumn = 'name';
    /**
     * @var array
     */
    public $sortingColumn = ['root', 'lft'];

    public function getVerboseNameList()
    {
        $modelClass = $this->getModel();
        if (array_key_exists('id', $this->params) && $this->params['id']) {
            $qs = $modelClass::objects()->filter(['pk' => $this->params['id']]);
            $model = $qs->get();
            return $model->{$this->nestedColumn};
        } else {
            return parent::getVerboseNameList();
        }
    }

    public function index()
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        if (array_key_exists('id', $this->params) && $this->params['id']) {
            $qs = $modelClass::objects()->filter(['pk' => $this->params['id']]);
            $model = $qs->get();
            if ($model === null) {
                $this->error(404);
            }
            $qs = $model->objects()->children();
        } else {
            $model = new $modelClass();
            $qs = $model->objects()->roots();
        }

        $filterForm = null;
        $filterFormClass = $this->getFilterForm();
        if ($filterFormClass) {
            $filterForm = new $filterFormClass();
            $filterForm->populate($_GET);
            $attrs = $filterForm->getQsFilter();
            if (!empty($attrs)) {
                $qs->filter($attrs);
            }
        }

        $this->initBreadcrumbs($model);

        if ($this->sortingColumn) {
            $qs->order(is_array($this->sortingColumn) ? $this->sortingColumn : [$this->sortingColumn]);
        }

        $currentOrder = null;
        if (isset($this->params['order'])) {
            $column = $this->params['order'];
            $currentOrder = $column;
            if (substr($column, 0, 1) === '-') {
                $column = ltrim($column, '-');
                $sort = "-";
            } else {
                $sort = "";
            }
            $qs = $qs->order([$sort . $column]);
        }

        $qs = $this->search($qs);

        $table = new AdminTable($qs, [
            'admin' => $this,
            'moduleName' => $this->moduleName,
            'sortingColumn' => $this->sortingColumn,
            'columns' => $this->getColumns(),
            'linkColumn' => $this->linkColumn,
            'paginationConfig' => [
                'pageSize' => $this->pageSize
            ]
        ]);

        return [
            'columns' => $this->getColumns(),
            'table' => $table,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), $this->getParentBreadcrumbs($model)),
            'sortingColumn' => $this->sortingColumn,
            'currentOrder' => $currentOrder,
            'searchFields' => $this->getSearchFields(),
            'filterForm' => $filterForm
        ];
    }

    public function update($pk, array $data = [], array $files = [])
    {
        $context = parent::update($pk, $data, $files);
        $context['breadcrumbs'] = array_merge($this->getBreadcrumbs(), $this->getParentBreadcrumbs($context['model']));
        return $context;
    }

    public function getParentBreadcrumbs($model)
    {
        $parents = [];

        if ($model->pk) {
            $parents = $model->objects()->ancestors()->order(['lft'])->all();
            $parents[] = $model;
        }

        $breadcrumbs = [];
        foreach ($parents as $parent) {
            $breadcrumbs[] = [
                'url' => Mindy::app()->urlManager->reverse('admin:list_nested', [
                    'module' => $model->getModuleName(),
                    'adminClass' => $this->classNameShort(),
                    'id' => $parent->pk
                ]),
                'name' => (string)$parent,
                'items' => []
            ];
        }
        return $breadcrumbs;
    }

    public function sorting(array $data = [])
    {
        if (!isset($data['pk'])) {
            throw new Exception("Failed to receive primary key");
        }

        /** @var \Mindy\Orm\TreeModel $modelClass */
        $modelClass = $this->getModel();

        /** @var \Mindy\Orm\TreeModel $model */
        $model = $modelClass::objects()->filter(['pk' => $data['pk']])->get();
        if (!$model) {
            throw new Exception("Model not found");
        }

        if ($model->getIsRoot()) {
            $models = $data['models'];

            $roots = $modelClass::objects()->roots()->filter(['pk__in' => $models])->all();

            $dataPk = [];
            $newPositions = [];
            $oldPositions = [];
            $descendants = [];

            foreach ($models as $position => $pk) {
                $newPositions[$pk] = $position;
            }

            foreach ($roots as $root) {
                $oldPositions[$root->pk] = $root->root;
                $descendants[$root->pk] = $root->objects()->descendants()->filter([
                    'level__gt' => 1
                ])->valuesList(['pk'], true);
            }

            /**
             * Pager-independent sorting
             */
            asort($oldPositions);
            foreach ($newPositions as $pk => $position) {
                $dataPk[$pk] = array_shift($oldPositions);
            }

            foreach ($roots as $root) {
                $modelClass::objects()->filter(['pk__in' => $descendants[$root->pk]])->update(['root' => $dataPk[$root->pk]]);
            }

            foreach ($dataPk as $pk => $position) {
                $modelClass::objects()->filter(['pk' => $pk])->update(['root' => $position]);
            }
        } else {
            $target = null;
            if (isset($data['insertBefore'])) {
                $target = $modelClass::objects()->filter(['pk' => $data['insertBefore']])->get();
                if (!$target) {
                    throw new Exception("Target not found");
                }
                $model->moveBefore($target);
            } else if (isset($data['insertAfter'])) {
                $target = $modelClass::objects()->filter(['pk' => $data['insertAfter']])->get();
                if (!$target) {
                    throw new Exception("Target not found");
                }
                $model->moveAfter($target);
            } else {
                throw new Exception("Missing required parameter insertAfter or insertBefore");
            }
        }
    }
}

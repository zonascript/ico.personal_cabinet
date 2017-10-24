<?php

namespace Modules\Admin\Components;

use Exception;
use Mindy\Base\ApplicationList;
use Mindy\Base\Mindy;
use Mindy\Form\ModelForm;
use Mindy\Helper\Creator;
use Mindy\Helper\Csv;
use Mindy\Helper\Text;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Traits\HttpErrors;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\OneToOneField;
use Mindy\Orm\Model;
use Mindy\Orm\Q\OrQ;
use Mindy\Orm\QuerySet;
use Modules\Admin\AdminModule;
use Modules\Admin\Tables\AdminTable;
use Modules\Core\CoreModule;
use Modules\Meta\Components\MetaTrait;

abstract class ModelAdmin
{
    use Accessors, Configurator, MetaTrait, ApplicationList, HttpErrors;

    public $adminTableClassName = '\Modules\Admin\Tables\AdminTable';
    /**
     * @var string or array
     */
    public $sortingColumn = null;
    /**
     * @var int
     */
    public $pageSize;
    /**
     * @var array
     */
    public $params = [];
    /**
     * @var string
     */
    public $updateTemplate = 'admin/admin/update.html';
    /**
     * @var string
     */
    public $createTemplate = 'admin/admin/create.html';
    /**
     * @var string
     */
    public $indexTemplate = 'admin/admin/_list.html';
    /**
     * @var string
     */
    public $actionsTemplate = 'admin/admin/_actions.html';
    /**
     * @var string
     */
    public $infoTemplate = 'admin/admin/info.html';
    /**
     * @var string
     */
    public $infoPrintTemplate = 'admin/admin/info_print.html';
    /**
     * @var string
     */
    public $linkColumn;
    /**
     * @var bool
     */
    public $showPkColumn = true;
    /**
     * @var string
     */
    protected $moduleName;
    /**
     * Collect this admin in AutoAdminTrait
     * @var bool
     */
    public $autoCollect = true;

    public function setModuleName($name)
    {
        $this->moduleName = $name;
    }

    public function getCanCreate()
    {
        return true;
    }

    public function getSearchFields()
    {
        return [];
    }

    public function getActions()
    {
        return [
            'remove' => AdminModule::t('Remove'),
            // Uncomment if you need export data to csv
            // 'exportCsv' => AdminModule::t('Export to csv file')
        ];
    }

    public function getActionsList()
    {
        return ['update', 'delete', 'view'];
    }

    public function getColumns()
    {
        $model = $this->getModel();
        return array_keys($model->getFieldsInit());
    }

    public function verboseName($column)
    {
        $model = $this->getModel();
        if (array_key_exists($column, $this->verboseNames())) {
            return $this->verboseNames()[$column];
        } elseif ($model->hasField($column)) {
            $field = $model->getField($column);
            if ($field) {
                return $field->getVerboseName($model);
            }
        }
        return $column;
    }

    /**
     * Verbose names for custom columns
     * @return array
     */
    public function verboseNames()
    {
        return [];
    }

    public function orderColumn($column)
    {
        $model = $this->getModel();
        $columns = $this->orderColumns();
        if (array_key_exists($column, $columns)) {
            return $columns[$column];
        } elseif ($model->hasField($column)) {
            return $column;
        }
        return null;
    }

    public function orderColumns()
    {
        return [];
    }


    /**
     * @param $column
     * @param $model
     * @return mixed
     */
    public function getColumnValue($column, $model)
    {
        list($column, $model) = $this->getChainedModel($column, $model);
        if ($model === null) {
            return null;
        }

        if ($column == 'pk') {
            $column = $model->getPkName();
        }

        if ($model->hasAttribute($column)) {
            return $model->getAttribute($column);
        } else {
            if ($model->hasField($column)) {
                return $model->__get($column);
            } else {
                $method = 'get' . ucfirst($column);
                if (method_exists($model, $method)) {
                    return $model->{$method}();
                }
            }
        }
        return null;
    }


    public function getChainedModel($column, $model)
    {
        if (strpos($column, '__') !== false) {
            $exploded = explode('__', $column);
            $last = count($exploded) - 1;
            $column = null;
            foreach ($exploded as $key => $name) {
                if ($model instanceof Model) {
                    $value = $model->{$name};
                    $column = $name;
                    if ($key != $last && $value) {
                        $model = $value;
                    }
                } else {
                    $model = null;
                    break;
                }
            }
        }
        return [$column, $model];
    }

    /**
     * @param null|int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize = null)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param Model $model
     * @return QuerySet
     */
    public function getQuerySet(Model $model)
    {
        return $model->objects()->getQuerySet();
    }

    /**
     * @return array
     */
    public function index()
    {
        $modelClass = $this->getModel();

        /* @var $model \Mindy\Orm\Model */
        $model = new $modelClass();

        /* @var $qs \Mindy\Orm\QuerySet */
        $qs = $this->getQuerySet($model);

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
        } elseif ($this->sortingColumn) {
            $qs->order([$this->sortingColumn]);
        }

        if (isset($this->params['search'])) {
            $qs = $this->search($qs);
        }

        $table = Creator::createObject($this->adminTableClassName, $qs, [
            'admin' => $this,
            'showPkColumn' => $this->showPkColumn,
            'sortingColumn' => $this->sortingColumn,
            'moduleName' => $this->moduleName,
            'currentOrder' => $currentOrder,
            'columns' => $this->getColumns(),
            'linkColumn' => $this->linkColumn,
            'actionsTemplate' => $this->actionsTemplate,
            'paginationConfig' => [
                'pageSize' => $this->pageSize
            ]
        ]);

        return [
            'table' => $table,
            'filterForm' => $filterForm,
            'breadcrumbs' => $this->getBreadcrumbs(),
            'currentOrder' => $currentOrder,
            'sortingColumn' => $this->sortingColumn,
            'searchFields' => $this->getSearchFields()
        ];
    }

    /**
     * Class name of filter form
     * Must be a instance of \Modules\Admin\Components\Filter
     * @return string
     */
    public function getFilterForm()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getCreateForm()
    {
        return ModelForm::className();
    }

    /**
     * @return string
     */
    public function getUpdateForm()
    {
        return $this->getCreateForm();
    }

    /**
     * @return array
     */
    public function getCreateFormParams()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getUpdateFormParams()
    {
        return $this->getCreateFormParams();
    }

    /**
     * @return \Mindy\Orm\Model
     */
    abstract public function getModel();

    /**
     * @return \Mindy\Base\Module
     */
    public function getModule()
    {
        return $this->getModel()->getModule();
    }

    public function formatBreadcrumbs(array $menu = [])
    {
        $name = Mindy::app()->getModule($this->getModule()->id)->getName();
        foreach ($menu as $item) {
            if ($item['name'] == $name) {
                return $item['items'];
            }
        }
        return [];
    }

    /**
     * Формирование хлебных крошек. Модель используется в
     * `NestedAdmin` для построения хлебных крошек в соответствии с деревом.
     * @param $model
     */
    public function initBreadcrumbs($model)
    {
        $this->addBreadcrumb($this->getModule()->getName());

        $this->addBreadcrumb(
            Text::mbUcfirst($this->getVerboseName()),
            Mindy::app()->urlManager->reverse('admin:list', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort()
            ])
        );
    }

    public function getInfoFields(Model $model)
    {
        return array_keys($model->getFieldsInit());
    }

    /**
     * @param $pk
     * @param array $data
     * @param array $files
     * @return array
     */
    public function update($pk, array $data = [], array $files = [])
    {
        $modelClass = $this->getModel();
        $model = $modelClass::objects()->filter(['pk' => $pk])->get();

        if ($model === null) {
            $this->error(404);
        }

        if (!is_string($modelClass)) {
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $formClass = $this->getUpdateForm();
        /* @var $form \Mindy\Form\ModelForm */
        $form = new $formClass(array_merge($this->getUpdateFormParams(), [
            'model' => $model,
            'instance' => $model
        ]));

        if (!empty($data) && $form->populate($data, $files)->isValid() && $form->save()) {
            Mindy::app()->request->flash->success(CoreModule::t('Changes saved'));
            $this->redirectNext($data, $form);
        }

        return [
            'admin' => $this,
            'model' => $model,
            'form' => $form,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => $this->getVerboseNameUpdate($model)]
            ])
        ];
    }

    public function info($pk, array $data = [])
    {
        $modelClass = $this->getModel();
        $model = $modelClass::objects()->filter(['pk' => $pk])->get();

        if (!is_string($modelClass)) {
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $fields = [];
        foreach ($this->getInfoFields($model) as $fieldName) {
            if ($fieldName === 'pk') {
                $fieldName = $model::getPkName();
            }
            $fields[$fieldName] = $model->getField($fieldName);
        }

        return [
            'admin' => $this,
            'model' => $model,
            'fields' => $fields,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => (string)$model]
            ])
        ];
    }

    public function infoPrint($pk, array $data = [])
    {
        $modelClass = $this->getModel();
        $model = $modelClass::objects()->filter(['pk' => $pk])->get();

        if (!is_string($modelClass)) {
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $fields = [];
        foreach ($this->getInfoFields($model) as $fieldName) {
            if ($fieldName === 'pk') {
                $fieldName = $model::getPkName();
            }
            $fields[$fieldName] = $model->getField($fieldName);
        }
        return [
            'admin' => $this,
            'model' => $model,
            'fields' => $fields,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => (string)$model]
            ])
        ];
    }

    public function create(array $data = [], array $files = [])
    {
        $modelClass = $this->getModel();
        if (is_string($modelClass)) {
            $model = new $modelClass;
        } else {
            $model = $modelClass;
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $formClass = $this->getCreateForm();

        /* @var $form \Mindy\Form\ModelForm */
        $form = new $formClass(array_merge($this->getCreateFormParams(),[
            'model' => $model,
            'instance' => $model,
        ]));

        if (!empty($data) && $form->populate($data, $files)->isValid() && $form->save()) {
            Mindy::app()->request->flash->success(CoreModule::t('Changes saved'));
            $this->redirectNext($data, $form);
        }

        return [
            'admin' => $this,
            'form' => $form,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => $this->getVerboseNameCreate()]
            ])
        ];
    }

    public function delete($pk)
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        if ($model = $modelClass::objects()->get(['pk' => $pk])) {
            $model->delete();
        }
    }

    public function redirectNext($data, $form)
    {
        list($route, $params) = $this->getNextRoute($data, $form);
        if ($route && $params) {
            $this->redirect($route, $params);
        }
    }

    public function getNextRoute(array $data, $form)
    {
        $model = $form->getInstance();
        if (array_key_exists('save_continue', $data)) {
            return ['admin:update', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort(),
                'id' => $model->pk
            ]];
        } else if (array_key_exists('save_create', $data)) {
            return ['admin:create', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort()
            ]];
        } else {
            return ['admin:list', [
                'module' => $this->getModule()->getId(),
                'adminClass' => $this->classNameShort()
            ]];
        }
    }

    public function remove(array $data = [])
    {
        $models = isset($data['models']) ? $data['models'] : [];
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        foreach ($models as $pk) {
            if ($model = $modelClass::objects()->get(['pk' => $pk])) {
                $model->delete();
            }
        }

        $this->redirect('admin:list', ['module' => $this->getModel()->getModuleName(), 'adminClass' => $this->classNameShort()]);
    }


    public function exportCsv(array $data = [])
    {
        $qs = $this->getQuerySet($this->getModel());
        if (isset($data['models'])) {
            $qs->filter(['pk__in' => $data['models']]);
        }
        $exportData = [];
        $header = [];

        $modelsIterator = $qs->batch(100);
        $noExportFieldsClassName = [
            ForeignField::className(),
            HasManyField::className(),
            ManyToManyField::className(),
            OneToOneField::className()
        ];
        foreach ($modelsIterator as $models) {
            foreach ($models as $model) {
                $fields = $model->getFieldsInit();
                $line = [];
                foreach ($fields as $attribute => $field) {

                    if (in_array($field->className(), $noExportFieldsClassName)){
                        continue;
                    }

                    $verboseName = $field->getVerboseName($model);
                    if (!array_key_exists($verboseName, $header)) {
                        $header[$verboseName] = $verboseName;
                    }
                    $line[] = $model->{$attribute};
                }
                $exportData[] = $line;
            }
        }
        $this->forceCsv($header, $exportData);
    }

    protected function forceCsv($header, $data)
    {
        $helper = new Csv();
        if (method_exists($helper, 'createCsv')) {
            $content = $helper->createCsv($header, $data);
            Mindy::app()->request->http->sendFile($this->getExportCsvFileName(), $content);
        }
    }

    public function getExportCsvFileName()
    {
        return date('Y.m.d H:i:s') . '.csv';
    }

    public function sorting(array $data = [])
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        if (isset($data['models'])) {
            $models = $data['models'];
        } else {
            throw new Exception("Failed to receive models");
        }
        /**
         * Pager-independent sorting
         */
        $oldPositions = [];
        foreach ($models as $pk) {
            $oldPositions[] = $modelClass::objects()->filter(['pk' => $pk])->get()->{$this->sortingColumn};
        }
        asort($oldPositions);
        foreach ($models as $pk) {
            $newPosition = array_shift($oldPositions);
            $modelClass::objects()->filter(['pk' => $pk])->update([$this->sortingColumn => $newPosition]);
        }
        if (Mindy::app()->request->getIsAjax()) {
            Mindy::app()->controller->json(['success' => true]);
        } else {
            $this->redirect('admin:list', ['module' => $this->getModel()->getModuleName(), 'adminClass' => $this->classNameShort()]);
        }
    }

    /**
     * @param \Mindy\Orm\QuerySet|\Mindy\Orm\Manager $qs
     * @return mixed
     */
    public function search($qs)
    {
        $fields = $this->getSearchFields();
        if (isset($this->params['search']) && !empty($fields)) {
            $filters = [];
            foreach ($fields as $field) {
                $lookup = 'contains';
                $field_name = $field;
                if (strpos($field, '=') === 0) {
                    $field_name = substr($field, 1);
                    $lookup = 'exact';
                }

                $filters[] = [
                    implode('__', [$field_name, $lookup]) => $this->params['search']
                ];
            }
            $qs->filter([
                new OrQ($filters)
            ]);
        }
        return $qs;
    }

    public function redirect($route, $data = null)
    {
        $app = Mindy::app();
        $app->request->redirect($app->urlManager->reverse($route, $data));
    }

    public function getNames($model = null)
    {
        return $this->getModel()->getAdminNames($model);
    }

    public function getVerboseName()
    {
        $names = $this->getNames();
        return isset($names[0]) ? $names[0] : strtolower($this->getModel()->classNameShort());
    }

    public function getVerboseNameCreate()
    {
        $names = $this->getNames();
        return isset($names[1]) ? $names[1] : strtolower($this->getModel()->classNameShort());
    }

    public function getVerboseNameUpdate($model = null)
    {
        $names = $this->getNames($model);
        return isset($names[2]) ? $names[2] : strtolower($this->getModel()->classNameShort());
    }

    public function getVerboseNameList()
    {
        return Text::mbUcfirst($this->getVerboseName());
    }
}

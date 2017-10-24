<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 11:14
 */

namespace Modules\Admin\Tables;

use Mindy\Base\Mindy;
use Mindy\Orm\TreeModel;
use Mindy\Table\Columns\TemplateColumn;
use Mindy\Table\Table;
use Modules\Admin\Components\ModelAdmin;

class AdminTable extends Table
{
    /**
     * @var string
     */
    public $moduleName;
    /**
     * @var string
     */
    public $sortingColumn;
    /**
     * @var bool
     */
    public $showPkColumn = true;
    /**
     * @var array
     */
    public $html = [
        'id' => 'table-main',
        'data-toggle' => 'checkboxes',
        'data-range' => 'true'
    ];
    /**
     * @var string
     */
    public $currentOrder;
    /**
     * @var string
     */
    public $linkColumn;
    /**
     * @var ModelAdmin
     */
    protected $admin;
    /**
     * @var array
     */
    private $_dynamicColumns = [];
    /**
     * @var string
     */
    public $actionsTemplate = 'admin/admin/_actions.html';

    public function init()
    {
        $this->html = array_merge($this->html, [
            'class' => $this->sortingColumn ? 'sortingColumn' : ''
        ]);
    }

    public function setAdmin(ModelAdmin $admin)
    {
        $this->admin = $admin;
    }

    public function getColumns()
    {
        $admin = $this->admin;
        $adminClass = $admin->classNameShort();
        $moduleName = $this->moduleName;

        $rawColumns = $this->_dynamicColumns;

        if ($this->linkColumn) {
            if (isset($rawColumns[$this->linkColumn])) {
                unset($rawColumns[$this->linkColumn]);
            }
            $columns = array_merge([
                $this->linkColumn => [
                    'class' => AdminLinkColumn::className(),
                    'name' => $this->linkColumn,
                    'admin' => $admin,
                    'moduleName' => $moduleName,
                    'currentOrder' => $this->currentOrder,
                    'html' => [
                        'align' => 'left'
                    ],
                    'route' => function ($record) use ($moduleName, $adminClass) {
                        $urlManager = Mindy::app()->urlManager;
                        if (is_a($record, TreeModel::className())) {
                            if ($record->isLeaf() === false) {
                                return $urlManager->reverse('admin:list_nested', [
                                    'moduleName' => $moduleName,
                                    'adminClass' => $adminClass,
                                    'pk' => $record->pk
                                ]);
                            }
                        }

                        return null;
                    }
                ]
            ], $rawColumns);
        } else {
            $columns = $rawColumns;
        }

        if ($this->showPkColumn) {
            $columns = array_merge([
                'pk' => [
                    'class' => AdminLinkColumn::className(),
                    'name' => $admin->getModel()->getPkName(),
                    'admin' => $admin,
                    'moduleName' => $moduleName,
                    'currentOrder' => $this->currentOrder,
                    'html' => [
                        'class' => 'td-id',
                        'align' => 'left'
                    ],
                    'route' => function ($record) use ($moduleName, $adminClass) {
                        // 'admin:update' moduleName adminClass model.pk
                        // 'admin:list_nested' moduleName adminClass model.pk
                        return Mindy::app()->urlManager->reverse('admin:update', [
                            'moduleName' => $moduleName,
                            'adminClass' => $adminClass,
                            'pk' => $record->pk
                        ]);
                    }
                ]
            ], $columns);
        }

        if ($this->sortingColumn) {
            $columns = array_merge([
                'check' => [
                    'class' => CheckColumn::className(),
                    'admin' => $admin,
                    'length' => $this->count()
                ],
                'sorting' => [
                    'class' => SortingColumn::className(),
                ]
            ], $columns);
        } else {
            $columns = array_merge([
                'check' => [
                    'class' => CheckColumn::className(),
                    'admin' => $admin,
                    'length' => $this->count()
                ],
            ], $columns);
        }

        $columns = array_merge($columns, [
            'actions' => [
                'class' => TemplateColumn::className(),
                'template' => $this->actionsTemplate,
                'title' => '',
                'html' => [
                    'class' => 'actions'
                ],
                'extra' => [
                    'admin' => $admin,
                    'adminClass' => $adminClass,
                    'moduleName' => $moduleName
                ],
                'virtual' => true
            ]
        ]);

        return $columns;
    }

    /**
     * @param $record
     * @return array
     */
    public function getRowHtmlAttributes($record)
    {
        return [
            // For checkboxes
            'data-pk' => $record->pk
        ];
    }

    public function getColumn($key)
    {
        return isset($this->_dynamicColumns[$key]) ? $this->_dynamicColumns[$key] : null;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        foreach ($columns as $key => $value) {
            if (is_numeric($key)) {
                $this->_dynamicColumns[$value] = [
                    'class' => AdminRawColumn::className(),
                    'name' => $value,
                    'admin' => $this->admin,
                    'moduleName' => $this->moduleName,
                    'currentOrder' => $this->currentOrder
                ];
            } else {
                if (is_string($value)) {
                    $this->_dynamicColumns[$key] = [
                        'class' => $value,
                        'name' => $key,
                        'admin' => $this->admin,
                        'moduleName' => $this->moduleName,
                        'currentOrder' => $this->currentOrder
                    ];
                } else {
                    $this->_dynamicColumns[$key] = array_merge($value, [
                        'admin' => $this->admin,
                        'moduleName' => $this->moduleName,
                        'currentOrder' => $this->currentOrder
                    ]);
                }
            }
        }
    }

    public function render()
    {
        return strtr($this->template, [
            '{html}' => $this->getHtmlAttributes(),
            '{caption}' => $this->renderCaption(),
            '{header}' => $this->renderHeader(),
            '{footer}' => $this->renderFooter(),
            '{body}' => $this->renderBody(),
            '{pager}' => $this->getPager()->render('admin/admin/_pager.html')
        ]);
    }
}

<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 16:26
 */

namespace Modules\Admin\Tables;

use Mindy\Base\Mindy;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Table\Columns\Column;

class AdminRawColumn extends Column
{
    /**
     * @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin
     */
    public $admin;
    /**
     * @var string
     */
    public $headCellTemplate = '<th {html}>{title}</th>';
    /**
     * @var string
     */
    public $currentOrder;
    /**
     * @var string
     */
    public $moduleName;

    public function getTitle()
    {
        return $this->admin->verboseName($this->name);
    }

    public function renderHeadCell()
    {
        $title = $this->getTitle();
        $classes = ['th-' . $this->name];
        $orderColumn = $this->admin->orderColumn($this->name);
        if ($orderColumn) {
            if ($this->currentOrder == $orderColumn) {
                $class[] = 'desc';
                $orderColumn = '-' . $orderColumn;
            } else {
                $class[] = 'asc';
            }

            $request = Mindy::app()->request;
            $urlManager = Mindy::app()->urlManager;
            $title = strtr('<a href="{url}?order={order}&search={search}&id={id}">{title}</a>', [
                '{title}' => $title,
                '{url}' => $urlManager->reverse('admin:list', [
                    'moduleName' => $this->moduleName,
                    'adminClass' => $this->admin->classNameShort()
                ]),
                '{order}' => $orderColumn,
                '{search}' => $request->getParam('search'),
                '{id}' => $request->getParam('id'),
            ]);
        }
        return strtr($this->headCellTemplate, [
            '{title}' => $title,
            '{html}' => $this->formatHtmlAttributes([
                'class' => implode(' ', $classes)
            ])
        ]);
    }

    public function getValue($record)
    {
        $value = $this->admin->getColumnValue($this->name, $record);
        list($column, $model) = $this->admin->getChainedModel($this->name, $record);
        if ($model === null) {
            return null;
        }

        $field = $model->getField($column, false);
        if ($field) {
            if (is_a($field, HasManyField::className()) || is_a($field, ManyToManyField::className())) {
                return null;
            } else if (is_a($field, BooleanField::className())) {
                return $value ? '<i class="icon checkmark"></i>' : '';
            } else if (!empty($field->choices) && array_key_exists($value, $field->choices)) {
                return $field->choices[$value];
            } else {
                return (string) $value;
            }
        } else {
            return (string) $model->{$column};
        }
    }
}

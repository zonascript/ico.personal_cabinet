<?php

namespace Modules\Menu\Forms;

use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\DropDownField;
use Mindy\Form\ModelForm;
use Modules\Menu\MenuModule;
use Modules\Menu\Models\Menu;

class MenuForm extends ModelForm
{
    public function getFields()
    {
        $model = $this->getInstance();
        return [
            'parent' => [
                'class' => DropDownField::className(),
                'label' => MenuModule::t('Parent'),
                'choices' => function () use ($model) {
                    $list = ['' => ''];
                    $qs = $model->tree()->order(['root', 'lft']);
                    if ($model->getIsNewRecord()) {
                        $parents = $qs->all();
                    } else {
                        $parents = $qs->exclude(['pk' => $model->pk])->all();
                    }
                    foreach ($parents as $model) {
                        $list[$model->pk] = str_repeat("..", $model->level ? $model->level - 1 : $model->level) . ' ' . (string)$model;
                    }

                    return $list;
                },
            ],
        ];
    }

    public function getModel()
    {
        return new Menu;
    }
}

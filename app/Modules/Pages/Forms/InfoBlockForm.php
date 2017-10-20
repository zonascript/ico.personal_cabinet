<?php

namespace Modules\Pages\Forms;


use Modules\Editor\Fields\EditorField;
use Modules\Pages\Models\InfoBlock;
use Xcart\App\Form\ModelForm;

class InfoBlockForm extends ModelForm
{

    public function getModel()
    {
        return new InfoBlock();
    }

    public function getFields()
    {
        return [
            'text' => [
                'class' => EditorField::className(),
                'label' => 'Editor'
            ]
        ];
    }
}
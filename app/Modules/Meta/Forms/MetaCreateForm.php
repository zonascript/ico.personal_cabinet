<?php

namespace Modules\Meta\Forms;

use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\TextAreaField;
use Mindy\Form\Fields\TextField;
use Mindy\Form\ModelForm;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;

class MetaCreateForm extends ModelForm
{
    public function getFields()
    {
        return [
            'is_custom' => [
                'class' => CheckboxField::className(),
                'label' => MetaModule::t('Is custom')
            ],
            'title' => [
                'class' => TextField::className(),
                'label' => MetaModule::t('Title')
            ],
            'description' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Description')
            ],
            'keywords' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Keywords')
            ],
        ];
    }

    public function getModel()
    {
        return new Meta;
    }
}

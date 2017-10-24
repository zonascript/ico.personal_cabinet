<?php

namespace Modules\Meta\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\TextAreaField;
use Mindy\Form\ModelForm;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;

class MetaForm extends ModelForm
{
    public $exclude = ['is_custom'];

    public function init()
    {
        parent::init();
        $onSite = Mindy::app()->getModule('Meta')->onSite;
        if (is_null($onSite) || $onSite === false) {
            $this->exclude[] = 'site';
        }
    }

    public function getFields()
    {
        return [
            'is_custom' => [
                'class' => CheckboxField::className(),
                'label' => MetaModule::t('Is custom')
            ],
            'title' => [
                'class' => CharField::className(),
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

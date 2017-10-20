<?php

namespace Modules\Meta\Forms;

use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;
use Xcart\App\Form\Fields\CharField;
use Xcart\App\Form\Fields\CheckboxField;
use Xcart\App\Form\Fields\TextAreaField;
use Xcart\App\Form\ModelForm;
use Xcart\App\Main\Xcart;

class MetaForm extends ModelForm
{
//    public $exclude = ['is_custom'];

    public function init()
    {
        parent::init();
        $onSite = Xcart::app()->getModule('Meta')->onSite;
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

    /**
     * @return array
     */
    public function getCleanedData()
    {
        return $this->cleanedData;
    }

    public function beforeSetAttributes($owner, array $attributes)
    {
        $attributes['is_custom'] = 1;

        return parent::beforeSetAttributes($owner, $attributes);
    }
}

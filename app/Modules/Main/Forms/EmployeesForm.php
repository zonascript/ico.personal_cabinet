<?php

namespace Modules\Main\Forms;

use Modules\Main\MainModule;
use Modules\Main\Models\EmployeesModel;
use Xcart\App\Form\Fields\CheckboxField;
use Xcart\App\Form\Fields\HiddenField;
use Xcart\App\Form\Fields\ImageField;
use Xcart\App\Form\ModelForm;

/**
 * Class PagesForm
 * @package Modules\Pages
 */
class EmployeesForm extends ModelForm
{
//    public function getFieldsets()
//    {
//        return [
//            MainModule::t('Main information') => [
//                'name', 'url',
//            ],
//            MainModule::t('Content') => [
//                'content_short', 'content'
//            ],
//            MainModule::t('Additional') => [
//                'published_at', 'file'
//            ],
//            MainModule::t('Display settings') => [
//                'view', 'view_children', 'sorting'
//            ]
//        ];
//    }

    public function getFields()
    {
        return [
            'isCeo' => CheckboxField::className(),
            'photo' => ImageField::className(),
            'position' => HiddenField::className(),
        ];
    }

    public function getModel()
    {
        return new EmployeesModel();
    }
}

<?php

namespace Modules\Pages\Admin;

use Modules\Admin\Contrib\Admin;
use Modules\Pages\Forms\PagesForm;
use Modules\Pages\Models\Page;
use Modules\Pages\PagesModule;

/**
 * Class PageAdmin
 * @package Modules\Pages
 */
class PageAdmin extends Admin
{
    public $linkColumn = 'name';

    public function getListColumns()
    {
        return ['name'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getForm()
    {
        return new PagesForm();
    }

    public function getModel()
    {
        return new Page();
    }

    public static function getName()
    {
        return PagesModule::t('Pages');
    }

//    public function getNames($model = null)
//    {
//        return [
//            PagesModule::t('Pages'),
//            PagesModule::t('Create page'),
//            PagesModule::t('Update page')
//        ];
//    }
//
//    public function getActions()
//    {
//        return array_merge(parent::getActions(), [
//            'publish' => PagesModule::t('Publish'),
//            'unpublish' => PagesModule::t('Unpublish'),
//        ]);
//    }
//
//    public function unpublish(array $data = [])
//    {
//        if (isset($data['models'])) {
//            Page::objects()->filter(['pk' => $data['models']])->update(['is_published' => false]);
//        }
//
//        $this->redirect('admin:list', [
//            'module' => $this->getModel()->getModuleName(),
//            'adminClass' => $this->classNameShort()
//        ]);
//    }
//
//    public function publish(array $data = [])
//    {
//        if (isset($data['models'])) {
//            Page::objects()->filter(['pk' => $data['models']])->update(['is_published' => true]);
//        }
//
//        $this->redirect('admin:list', [
//            'module' => $this->getModel()->getModuleName(),
//            'adminClass' => $this->classNameShort()
//        ]);
//    }
}


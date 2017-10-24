<?php

namespace Modules\Pages\Admin;

use Modules\Admin\Components\NestedAdmin;
use Modules\Pages\Forms\PagesForm;
use Modules\Pages\Models\Page;
use Modules\Pages\PagesModule;

/**
 * Class PageAdmin
 * @package Modules\Pages
 */
class PageAdmin extends NestedAdmin
{
    public $linkColumn = 'name';

    public function getColumns()
    {
        return ['name'];
    }

    public function getSearchFields()
    {
        return ['name', 'id'];
    }

    public function getCreateForm()
    {
        return PagesForm::class;
    }

    /**
     * @return Page
     */
    public function getModel()
    {
        return new Page;
    }

    public function getNames($model = null)
    {
        return [
            PagesModule::t('Pages'),
            PagesModule::t('Create page'),
            PagesModule::t('Update page')
        ];
    }

    public function getActions()
    {
        return array_merge(parent::getActions(), [
            'publish' => PagesModule::t('Publish'),
            'unpublish' => PagesModule::t('Unpublish'),
        ]);
    }

    public function unpublish(array $data = [])
    {
        if (isset($data['models'])) {
            Page::objects()->filter(['pk' => $data['models']])->update(['is_published' => false]);
        }

        $this->redirect('admin:list', [
            'module' => $this->getModel()->getModuleName(),
            'adminClass' => $this->classNameShort()
        ]);
    }

    public function publish(array $data = [])
    {
        if (isset($data['models'])) {
            Page::objects()->filter(['pk' => $data['models']])->update(['is_published' => true]);
        }

        $this->redirect('admin:list', [
            'module' => $this->getModel()->getModuleName(),
            'adminClass' => $this->classNameShort()
        ]);
    }
}


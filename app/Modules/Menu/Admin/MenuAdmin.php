<?php

namespace Modules\Menu\Admin;

use Modules\Admin\Components\NestedAdmin;
use Modules\Menu\Forms\MenuForm;
use Modules\Menu\MenuModule;
use Modules\Menu\Models\Menu;

class MenuAdmin extends NestedAdmin
{
    /**
     * @var string
     */
    public $linkColumn = 'name';

    public function getSearchFields()
    {
        return ['name'];
    }

    public function getColumns()
    {
        return ['name', 'slug', 'url'];
    }

    public function getCreateForm()
    {
        return MenuForm::className();
    }

    public function getModel()
    {
        return new Menu;
    }

    public function getVerboseName()
    {
        return MenuModule::t('menu');
    }

    public function getVerboseNamePlural()
    {
        return MenuModule::t('menu');
    }
}

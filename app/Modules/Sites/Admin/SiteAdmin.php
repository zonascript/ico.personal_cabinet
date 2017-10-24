<?php

namespace Modules\Sites\Admin;

use Mindy\Base\Mindy;
use Modules\Admin\Components\ModelAdmin;
use Modules\Sites\SitesModule;

class SiteAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['domain', 'name'];
    }

    public function getSearchFields()
    {
        return ['name'];
    }

    public function getFormClass()
    {
        $module = Mindy::app()->getModule('Sites');
        $formClass = $module->formClass;
        return $formClass::className();
    }

    public function getModel()
    {
        $module = Mindy::app()->getModule('Sites');
        $modelClass = $module->modelClass;
        return new $modelClass;
    }

    public function getVerboseName()
    {
        return SitesModule::t('site');
    }

    public function getVerboseNamePlural()
    {
        return SitesModule::t('sites');
    }
}

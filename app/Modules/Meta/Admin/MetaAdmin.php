<?php

namespace Modules\Meta\Admin;

use Mindy\Base\Mindy;
use Modules\Admin\Components\ModelAdmin;
use Modules\Meta\Forms\MetaForm;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;

class MetaAdmin extends ModelAdmin
{
    public function getSearchFields()
    {
        return ['url', 'title', 'description', 'keywords'];
    }

    public function getColumns()
    {
        $columns = ['title', 'url'];
        if (Mindy::app()->getModule('Meta')->onSite) {
            $columns[] = 'site';
        }
        return $columns;
    }

    public function getModel()
    {
        return new Meta;
    }

    public function getCreateForm()
    {
        return MetaForm::className();
    }

    public function getVerboseName()
    {
        return MetaModule::t('Meta information');
    }

    public function getVerboseNamePlural()
    {
        return MetaModule::t('Meta information');
    }
}

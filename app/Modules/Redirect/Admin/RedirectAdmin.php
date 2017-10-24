<?php

namespace Modules\Redirect\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Redirect\Forms\RedirectForm;
use Modules\Redirect\Models\Redirect;

class RedirectAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['from_url', 'to_url', 'type'];
    }

    public function getCreateForm()
    {
        return RedirectForm::className();
    }

    public function getModel()
    {
        return new Redirect;
    }
}


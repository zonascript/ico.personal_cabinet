<?php

namespace Modules\User\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\User\Forms\GroupForm;
use Modules\User\Models\Group;

/**
 * Class GroupAdmin
 * @package Modules\User
 */
class GroupAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['name', 'is_locked', 'is_visible'];
    }

    public function getCreateForm()
    {
        return GroupForm::className();
    }

    public function getModel()
    {
        return new Group;
    }
}

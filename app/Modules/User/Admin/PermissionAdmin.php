<?php

namespace Modules\User\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\User\Forms\PermissionForm;
use Modules\User\Models\Permission;
use Modules\User\UserModule;

/**
 * Class PermissionAdmin
 * @package Modules\User
 */
class PermissionAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return [
            'code',
            'name',
        ];
    }

    public function getCreateForm()
    {
        return PermissionForm::className();
    }

    public function getModel()
    {
        return new Permission;
    }
}


<?php

namespace Modules\Main\Admin;

use Modules\Admin\Contrib\Admin;
use Modules\Main\Forms\EmployeesForm;
use Modules\Main\MainModule;
use Modules\Main\Models\EmployeesModel;

/**
 * Class PageAdmin
 * @package Modules\Pages
 */
class EmployeesAdmin extends Admin
{
    public $sort = 'position';

    public function getSearchColumns()
    {
        return ['name', 'post'];
    }

    public function getForm()
    {
        return new EmployeesForm();
    }

    public function getModel()
    {
        return new EmployeesModel();
    }

    public static function getName()
    {
        return MainModule::t('Employees');
    }
}


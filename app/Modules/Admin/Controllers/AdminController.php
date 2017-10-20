<?php

namespace Modules\Admin\Controllers;

use Modules\Admin\Contrib\Admin;
use Xcart\App\Main\Xcart;

class AdminController extends BackendController
{
    public function all($module, $admin)
    {
        $admin = $this->getAdmin($module, $admin);
        $this->setBreadcrumbs($admin);
        $admin->all();
    }

    public function create($module, $admin)
    {
        $admin = $this->getAdmin($module, $admin);
        $this->setBreadcrumbs($admin, 'Создание');
        $admin->create();
    }

    public function update($module, $admin, $pk)
    {
        $admin = $this->getAdmin($module, $admin);
        $this->setBreadcrumbs($admin, 'Редактирование');
        $admin->update($pk);
    }

    public function remove($module, $admin, $pk)
    {
        if (!$this->getRequest()->getIsPost()) {
            $this->error(404);
        }
        $admin = $this->getAdmin($module, $admin);
        $admin->remove($pk);
    }

    public function sort($module, $admin)
    {
        $admin = $this->getAdmin($module, $admin);

        $pkList = isset($_POST['pk_list']) && is_array($_POST['pk_list']) ? $_POST['pk_list'] : [];
        $to = isset($_POST['to']) ? $_POST['to'] : null;
        $prev = isset($_POST['prev']) ? $_POST['prev'] : null;
        $next = isset($_POST['next']) ? $_POST['next'] : null;

        $admin->sort($pkList, $to , $prev, $next);
    }

    public function columns($module, $admin)
    {
        $admin = $this->getAdmin($module, $admin);

        $columns = isset($_POST['columns']) && is_array($_POST['columns']) ? $_POST['columns'] : [];

        $admin->setColumns($columns);
    }

    public function groupAction($module, $admin)
    {
        if (!$this->getRequest()->getIsPost()) {
            $this->error(404);
        }
        $admin = $this->getAdmin($module, $admin);
        $action = isset($_POST['action']) ? $_POST['action'] : null;
        $pkList = isset($_POST['pk_list']) && is_array($_POST['pk_list']) ? $_POST['pk_list'] : [];

        if ($action) {
            $admin->handleGroupAction($action, $pkList);
        } else {
            $this->error(404);
        }
    }

    /**
     * @param $admin Admin
     */
    public function setBreadcrumbs($admin, $last = null)
    {
        Xcart::app()->breadcrumbs->add(
            $admin->getName(),
            $admin->getAllUrl()
        );

        if ($last) {
            Xcart::app()->breadcrumbs->add($last);
        }
    }

    /**
     * @param $module
     * @param $admin
     * @return Admin
     */
    public function getAdmin($module, $admin)
    {
        $class = "Modules\\{$module}\\Admin\\{$admin}";
        if (class_exists($class)) {
            return new $class;
        }
        $this->error(404);
    }
}
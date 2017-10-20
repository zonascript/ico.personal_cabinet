<?php

namespace Modules\Admin\Controllers;

use Xcart\App\Controller\Controller;
use Xcart\App\Main\Xcart;

class BackendController extends Controller
{
    public function beforeAction($action, $params)
    {
        $user = Xcart::app()->auth->getUser();

        if (!$user || $user->getIsGuest()) {
            $this->getRequest()->redirect('admin:login');
        }
        elseif (!$user->getIsSuperuser()) {
            $this->error(403);
        }
    }
}
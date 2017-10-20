<?php

namespace Modules\Admin\Controllers;

class CommonController extends BackendController
{
    public function index()
    {
        echo $this->render('admin/index.tpl', [

        ]);
    }
}
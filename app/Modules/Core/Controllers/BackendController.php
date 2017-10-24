<?php

namespace Modules\Core\Controllers;

use Mindy\Base\ApplicationList;
use Mindy\Base\Mindy;
use Mindy\Http\Request;
use Modules\Core\CoreModule;
use Modules\Meta\Components\MetaTrait;

class BackendController extends Controller
{
    use ApplicationList, MetaTrait;

    public function __construct($id, $module = null, Request $request)
    {
        parent::__construct($id, $module, $request);

        $this->pageTitle = CoreModule::t('Control panel');

        $user = Mindy::app()->getUser();
        if ($user === null) {
            $this->getRequest()->redirect('admin:login');
        } else if ($user->is_superuser) {

        } else if ($user->is_staff == false) {
            $this->getRequest()->redirect('admin:login');
        }
    }

    public function accessRules()
    {
        return [
            // allow only authorized users
            ['allow' => true, 'users' => ['@']],
            // deny all users
            ['allow' => false, 'users' => ['*']],
        ];
    }

    public function render($view, array $data = [])
    {
        return parent::render($view, array_merge($data, [
            'apps' => $this->getApplications()
        ]));
    }
}

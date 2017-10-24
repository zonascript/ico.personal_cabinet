<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 03/07/14.07.2014 18:50
 */

namespace Modules\Admin\Controllers;

use Mindy\Base\ApplicationList;
use Mindy\Base\Mindy;
use Mindy\Helper\Text;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Admin\UserAdmin;
use Modules\User\Forms\ChangePasswordForm;
use Modules\User\Forms\LoginForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

class AuthController extends FrontendController
{
    use ApplicationList;

    public $defaultAction = 'login';

    public $defaultRedirectUrl = '/';

    public function allowedActions()
    {
        return ['login', 'logout'];
    }

    public function init()
    {
        parent::init();

        $this->defaultRedirectUrl = Mindy::app()->urlManager->reverse('admin:index');

        if (isset($_GET['redirectUrl'])) {
            Mindy::app()->auth->setReturnUrl($_GET['redirectUrl']);
        }
    }

    public function actionLogin()
    {
        $r = $this->getRequest();
        $form = new LoginForm();
        if ($r->getIsPost() && $form->populate($_POST)->isValid() && $form->login()) {
            $r->redirect('admin:index');
        }

        echo $this->render('admin/login.html', [
            'form' => $form
        ]);
    }

    /**
     * Logout the current user and redirect to returnLogoutUrl.
     */
    public function actionLogout()
    {
        $auth = Mindy::app()->auth;
        if ($auth->isGuest) {
            $this->getRequest()->redirect(Mindy::app()->homeUrl);
        }

        $auth->logout();
        $this->getRequest()->redirect('admin:login');
    }

    public function actionChangepassword($id)
    {
        $auth = Mindy::app()->auth;
        if ($auth->isGuest) {
            $this->getRequest()->redirect(Mindy::app()->homeUrl);
        }

        $model = User::objects()->filter(['pk' => $id])->get();
        if ($model === null) {
            $this->error(404);
        }

        $admin = new UserAdmin;
        $this->addBreadcrumb(Text::mbUcfirst($admin->getVerboseName()), Mindy::app()->urlManager->reverse('admin:list', [
            'module' => User::getModuleName(),
            'adminClass' => $admin->classNameShort()
        ]));
        $this->addBreadcrumb((string)$model, Mindy::app()->urlManager->reverse('admin:update', [
            'module' => User::getModuleName(),
            'adminClass' => $admin->classNameShort(),
            'id' => $id
        ]));
        $this->addBreadcrumb(UserModule::t('Change password'));

        $form = new ChangePasswordForm(['model' => $model]);
        $r = $this->getRequest();
        if ($r->isPost && $form->populate($_POST)->isValid() && $form->save()) {
            $r->flash->success(UserModule::t('Password changed'));
            $r->http->refresh();
        }

        echo $this->render('admin/changepassword.html', [
            'model' => $model,
            'form' => $form
        ]);
    }

    public function render($view, array $data = [])
    {
        $data['apps'] = $this->getApplications();
        return parent::render($view, $data);
    }
}

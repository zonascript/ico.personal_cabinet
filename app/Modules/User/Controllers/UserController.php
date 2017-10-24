<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Mindy\Pagination\Pagination;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Forms\ChangePasswordForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class UserController
 * @package Modules\User
 */
class UserController extends FrontendController
{
    public function allowedActions()
    {
        return ['view'];
    }

    public function beforeAction($owner, $action)
    {
        $user = Mindy::app()->getUser();

        if ($user->isGuest) {
            $this->request->redirect(Mindy::app()->getModule('user')->getLoginUrl());
        }

        if ($action->getId() == 'list' && $this->getModule()->userList === false && !$user->is_superuser) {
            $this->error(404);
        }

        return true;
    }

    public function actionChangepassword()
    {
        $model = Mindy::app()->user;
        $this->addBreadcrumb(UserModule::t("Change password"));

        $form = new ChangePasswordForm([
            'model' => $model
        ]);

        if ($this->request->isPost && $form->populate($_POST)->isValid() && $form->save()) {
            $this->request->flash->success(UserModule::t('Password changed'));
            $this->request->redirect('user:login');
        }

        echo $this->render('user/change_password.html', [
            'form' => $form,
            'model' => $model
        ]);
    }
}

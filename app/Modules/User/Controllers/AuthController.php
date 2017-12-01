<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Forms\LoginForm;
use Modules\User\Forms\RegistrationForm;
use Modules\User\UserModule;

/**
 * Class AuthController
 * @package Modules\User
 */
class AuthController extends FrontendController
{
    public function allowedActions()
    {
        return ['login', 'logout'];
    }

    public function actionLogin()
    {
        $app = Mindy::app();
        $module = Mindy::app()->getModule('User');

        if (!$app->user->isGuest) {
            $this->request->redirect($module->loginRedirectUrl);
        }

        $this->addBreadcrumb(UserModule::t("Login"));

        $form = new LoginForm();
        if ($this->request->isPost && $form->populate($_POST)->isValid() && $form->login()) {
            $this->redirectNext();

            if ($this->request->isAjax) {
                echo $this->json([
                    'status' => 'success',
                    'title' => UserModule::t('You have successfully logged in to the site')
                ]);
            } else {
                $this->request->redirect($module->loginRedirectUrl);
            }
        }

        echo $this->render('user/login.html', [
            'form' => $form,
            'register_form' => new RegistrationForm(),
        ]);
    }

    /**
     * Logout the current user and redirect to returnLogoutUrl.
     */
    public function actionLogout()
    {
        $auth = Mindy::app()->auth;
        if ($auth->isGuest) {
            $this->redirectNext();
            $this->request->redirect(Mindy::app()->homeUrl);
        }

        $auth->logout($this->getModule()->destroySessionAfterLogout);
        $this->request->redirect('user:login');
    }
}

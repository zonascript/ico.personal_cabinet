<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Mindy\Pagination\Pagination;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Forms\ChangePasswordForm;
use Modules\User\Forms\GoogleAuthForm;
use Modules\User\Forms\ProfileForm;
use Modules\User\Models\User;
use Modules\User\UserModule;
use PHPGangsta_GoogleAuthenticator;

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

    public function actionSettings()
    {
        $user = Mindy::app()->user;

        $form = new ChangePasswordForm([
            'model' => $user
        ]);

        $profile_form = new ProfileForm([
            'model' => $user,
            'instance' => $user,
        ]);

        $ga = new PHPGangsta_GoogleAuthenticator();

        $google_auth = new GoogleAuthForm();


        if ($this->request->isPost && ($this->request->post->get('key') == 'profile_form') && $profile_form->populate($_POST)->isValid() && $profile_form->save()) {
            $this->request->flash->success(UserModule::t('Profile changed'));
            $this->request->redirect('user:settings');
        }

        if ($this->request->isPost && ($this->request->post->get('key') == 'pass_form') && $form->populate($_POST)->isValid() && $form->save()) {
            $this->request->flash->success(UserModule::t('Password changed'));
            $this->request->redirect('user:settings');
        }

        if ($this->request->isPost && ($this->request->post->get('key') == 'google_auth_form')) {

            if ($google_auth->populate($_POST)->isValid()) {

                if ($google_auth->google_code->getValue() === $ga->getCode($user->google_secret)) {

                    if ($this->request->post->get('mode') == 'enable') {
                        $user->is_2fa = true;
                        $this->request->flash->success(UserModule::t('Two-factor authentification enabled'));
                    } elseif ($this->request->post->get('mode') == 'disable') {
                        $user->is_2fa = false;
                        $this->request->flash->success(UserModule::t('Two-factor authentification disabled'));
                    }

                    $user->save(['is_2fa']);

                } else {
                    $this->request->flash->error(UserModule::t('Invalid two-factor authentification code'));
                }
                $this->request->redirect('user:settings');
            }
        } else {
            if (!$user->is_2fa) {

                $user->google_secret = $ga->createSecret();
                $user->save(['google_secret']);
            }
        }

        echo $this->render('user/settings.html',
            [
                'model' => $user,
                'pass_form' => $form,
                'profile_form' => $profile_form,
                'google_auth' => $google_auth,
                'google_secret' => $ga
            ]
        );
    }
}

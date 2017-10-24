<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Components\ParamsHelper;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Forms\RecoverForm;
use Modules\User\Forms\RecoverPasswordForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class RecoverController
 * @package Modules\User
 */
class RecoverController extends FrontendController
{
    public function actionIndex()
    {
        $enabled = ParamsHelper::get('user.user.recover');
        if (!$enabled) {
            $this->error(404);
        }

        $this->addBreadcrumb(UserModule::t("Recover password"));

        $form = new RecoverForm();
        if ($this->request->isPost) {
            if ($form->populate($_POST)->isValid() && $form->send()) {
                $this->request->flash->success(UserModule::t("Message was sended to your email"));
                echo $this->render('user/recover_form_success.html');
                Mindy::app()->end();
            } else {
                $this->request->flash->error(UserModule::t("An error has occurred. Please try again later."));
            }
        }
        echo $this->render('user/recover_form.html', [
            'form' => $form
        ]);
    }

    public function actionActivate($key)
    {
        $model = User::objects()->filter(['activation_key' => $key])->get();
        if ($model === null) {
            $this->error(404);
        }

        if ($model->activation_key === $key) {
            $form = new RecoverPasswordForm([
                'model' => $model
            ]);
            if ($this->request->isPost && $form->populate($_POST)->isValid() && $form->save()) {
                $this->request->flash->success(UserModule::t('Password changed'));
                $this->request->redirect('user:login');
            } else {
                echo $this->render('user/recover_change_password.html', [
                    'form' => $form,
                    'model' => $model,
                    'key' => $key
                ]);
            }
        } else {
            echo $this->render('user/change_password_incorrect.html');
        }
    }
}

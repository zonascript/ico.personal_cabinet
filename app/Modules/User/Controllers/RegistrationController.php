<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Components\ParamsHelper;
use Modules\Core\Controllers\FrontendController;
use Modules\User\Forms\RegistrationForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class RegistrationController
 * @package Modules\User
 */
class RegistrationController extends FrontendController
{
    public function allowedActions()
    {
        return ['index'];
    }

    public function actionIndex()
    {
        $enabled = ParamsHelper::get('user.user.registration');
        if (!$enabled) {
            $this->error(404);
        }

        $this->addBreadcrumb(UserModule::t("Registration"));

        $form = new RegistrationForm();
        if ($this->request->isPost && $form->populate($_POST)->isValid() && $form->save()) {
            $module = Mindy::app()->getModule('User');
            $this->request->redirect($module->registrationRedirectUrl);
        }

        echo $this->render('user/registration.html', [
            'form' => $form
        ]);
    }

    public function actionSuccess()
    {
        echo $this->render('user/registration_success.html');
    }

    public function actionActivate($key)
    {
        $model = User::objects()->filter(['activation_key' => $key])->get();
        if ($model === null) {
            $this->error(404);
        }

        if ($model->is_active) {
            $this->request->redirect('user:login');
        }

        if ($model->activation_key === $key) {
            $model->is_active = true;
            $model->save(['is_active']);

            echo $this->render('user/registration_activation_success.html');
        } else {
            echo $this->render('user/registration_activation_failed.html');
        }
    }
}

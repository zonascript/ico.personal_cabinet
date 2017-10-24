<?php

namespace Modules\User\Controllers;

use Mindy\Base\Mindy;
use Mindy\Helper\Password;
use Modules\Core\Controllers\ApiBaseController;
use Modules\User\Forms\LoginForm;
use Modules\User\Forms\RegistrationForm;
use Modules\User\Models\Key;
use Modules\User\Models\User;
use Modules\User\UserModule;

class AuthApiController extends ApiBaseController
{
    public function actionLogin()
    {
        $app = Mindy::app();
        if (!$app->user->getIsGuest()) {
            $model = $app->getUser();
            echo $this->json([
                'status' => true,
                'errors' => [],
                'user' => [
                    'id' => $model->pk,
                    'username' => $model->username,
                    'email' => $model->email,
                    'groups' => $model->groups->asArray()->all(),
                    'permissions' => $model->permissions->asArray()->all()
                ],
                'message' => UserModule::t('You have successfully logged in to the site')
            ]);
            $this->end();
        }

        $form = new LoginForm();
        $r = $this->getRequest();
        if ($r->getIsPost() && $form->setAttributes($_POST)->isValid() && $form->login()) {
            $model = $form->getUser();

            $this->clearKeys($model);
            $authKey = md5(Password::generateSalt());

            $key = new Key([
                'user' => $model,
                'key' => $authKey
            ]);
            $key->save();

            $data = [
                'errors' => [],
                'status' => true,
                'user' => [
                    'id' => $model->pk,
                    'username' => $model->username,
                    'groups' => $model->groups->asArray()->all(),
                    'permissions' => $model->permissions->asArray()->all(),
                    'token' => $authKey
                ],
                'message' => UserModule::t('You have successfully logged in to the site')
            ];
        } else {
            $data = [
                'errors' => $form->getErrors()
            ];
        }
        echo $this->json($data);
        $this->end();
    }

    protected function clearKeys(User $model)
    {
        Key::objects()->filter([
            'user' => $model
        ])->delete();
    }

    public function actionLogout()
    {
        /** @var \Modules\User\Components\Auth $auth */
        $auth = Mindy::app()->auth;
        if ($auth->getIsGuest() === false) {
            $this->clearKeys($auth->getModel());
            $auth->logout($this->getModule()->destroySessionAfterLogout);
        }

        echo $this->json([
            'status' => true
        ]);
        $this->end();
    }

    public function actionRegistration()
    {
        $form = new RegistrationForm();
        if ($form->setAttributes($_POST)->isValid()) {
            $user = $form->save();
            if ($user === false) {
                echo $this->json([
                    'status' => false,
                    'exception' => "Failed to save model"
                ]);
            } else {
                echo $this->json([
                    'id' => $user->pk,
                    'status' => true,
                    'errors' => []
                ]);
            }
            $this->end();
        } else {
            echo $this->json([
                'status' => false,
                'errors' => $form->getErrors()
            ]);
            $this->end();
        }
    }

    public function actionActivate($key)
    {
        $model = User::objects()->get(['activation_key' => $key]);
        if ($model === null) {
            echo $this->json([
                'status' => false,
                'error' => 'Key not found'
            ]);
            $this->end();
        }

        if ($model->is_active == false && $model->activation_key === $key) {
            $model->is_active = true;
            $model->save(['is_active']);

            echo $this->json([
                'status' => true,
                'is_active' => true
            ]);
        } else if ($model->is_active) {
            echo $this->json([
                'status' => true,
                'is_active' => true
            ]);
        } else {
            echo $this->json([
                'status' => false,
                'error' => 'Incorrect key'
            ]);
        }
    }
}

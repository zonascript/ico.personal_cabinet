<?php

namespace Modules\User\Controllers;

use Modules\Core\Controllers\ApiBaseController;
use Modules\User\Forms\ChangePasswordForm;
use Modules\User\Forms\RecoverForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class RecoverController
 * @package Modules\User
 */
class RecoverApiController extends ApiBaseController
{
    public function actionRecover($key = null)
    {
        if ($key) {
            $user = User::objects()->get(['activation_key' => $key]);
            if ($user === null) {
                echo $this->json([
                    'status' => false,
                    'error' => 'User not found'
                ]);
                $this->end();
            }

            $user->password = '';
            $user->save(['password']);

            $form = new ChangePasswordForm();
            $form->setModel($user);

            $r = $this->getRequest();
            if ($r->getIsPost() && $form->populate($_POST)->isValid() && $form->save()) {
                echo $this->json([
                    'status' => true,
                    'message' => UserModule::t('Password changed')
                ]);
                $this->end();
            } else {
                echo $this->json([
                    'errors' => $form->getJsonErrors()
                ]);
                $this->end();
            }
        } else {
            $form = new RecoverForm();
            if ($form->populate($_POST)->isValid() && $form->send()) {
                echo $this->json([
                    'status' => true
                ]);
            } else {
                echo $this->json([
                    'errors' => $form->getJsonErrors()
                ]);
            }
        }
    }
}

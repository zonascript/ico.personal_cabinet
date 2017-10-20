<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 19/05/16 07:48
 */

namespace Modules\Admin\Controllers;

use Modules\Admin\Forms\LoginForm;
use Xcart\App\Controller\Controller;
use Xcart\App\Main\Xcart;

class AuthController extends Controller
{
    public function login()
    {
        /** @var \Modules\User\Models\UserModel $user */
        $user = Xcart::app()->getUser();
        if (!$user->getIsGuest()) {
            $this->redirect('admin:index');
        }
        $form = new LoginForm();
        if ($this->getRequest()->getIsPost() && $form->populate($_POST)) {
            if ($form->isValid()) {
                $form->login();
                $this->redirect('admin:index');
            }
        }
        echo $this->render('admin/auth/login.tpl', [
            'form' => $form
        ]);
    }

    public function logout()
    {
        Xcart::app()->auth->logout();
        $this->redirect('admin:login');
    }
}
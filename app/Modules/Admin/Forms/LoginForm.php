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
 * @date 07/08/16 16:17
 */

namespace Modules\Admin\Forms;

use Mindy\QueryBuilder\Q\QOr;
use Modules\User\Models\UserModel;
use Modules\User\UserModule;
use Xcart\App\Form\Fields\CharField;
use Xcart\App\Form\Fields\PasswordField;
use Xcart\App\Form\Form;
use Xcart\App\Main\Xcart;

class LoginForm extends Form
{
    public function getFields()
    {
        return [
            'login' => [
                'class' => CharField::className(),
                'label' => 'Login or Email',
                'required' => true
            ],
            'password' => [
                'class' => PasswordField::className(),
                'label' => 'Password',
                'required' => true
            ]
        ];
    }

    public function clean($attributes)
    {
        $email = $attributes['email'];
        $password = $attributes['password'];

        $hasher = UserModule::getPasswordHasher();
        
        $user = $this->getUser($email);
        if ($user) {
            if (!$hasher::verify($password, $user->password)) {
                $this->addError('password', 'Incorrect password');
            }
        } else {
            $this->addError('email', 'User not found');
        }
    }

    public function login()
    {
        $data = $this->getAttributes();
        $user = $this->getUser($data['login']);
        if ($user) {
            Xcart::app()->auth->login($user);
        }
    }

    public function getUser($login)
    {
        return UserModel::objects()->filter([ new QOr(['login' => $login, 'email' => $login])])->get();
    }
}
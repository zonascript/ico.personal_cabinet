<?php

namespace Modules\User\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\HiddenField;
use Mindy\Form\Fields\PasswordField;
use Mindy\Form\Form;
use Modules\User\Components\UserIdentity;
use Modules\User\UserModule;

/**
 * Class LoginForm
 * @package Modules\User
 */
class LoginForm extends Form
{
    private $_identity;

    public function getFields()
    {
        return [
            'username' => [
                'class' => CharField::className(),
                'label' => false,
                'html' => [
                    'placeholder' => UserModule::t('Email')
                ],
            ],
            'password' => [
                'class' => PasswordField::className(),
                'label' => false,
                'html' => [
                    'placeholder' => UserModule::t('Password')
                ]
            ],
            'google_code' => [
                'class' => CharField::className(),
                'label' => false,
                'html' => [
                    'placeholder' => UserModule::t('Google auth code')
                ],
            ],
            'rememberMe' => [
                'class' => HiddenField::className(),
                'label' => false,
                'value' => true
            ]
        ];
    }

    public function isValid()
    {
        parent::isValid();
        $this->authenticate();
        return $this->hasErrors() === false;
    }

    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     */
    public function authenticate()
    {
        if ($this->_identity === null) {
            $google_code = null;
            if ($this->hasField('google_code')) {
                $google_code = $this->google_code->getValue();
            }
            $this->_identity = new UserIdentity($this->username->getValue(), $this->password->getValue(), $google_code);
        }

        if (!$this->_identity->authenticate()) {
            switch ($this->_identity->errorCode) {
                case UserIdentity::ERROR_EMAIL_INVALID:
                    $this->addError("username", UserModule::t("Email is incorrect."));
                    break;
                case UserIdentity::ERROR_USERNAME_INVALID:
                    $this->addError("username", UserModule::t("Email is incorrect."));
                    break;
                case UserIdentity::ERROR_GOOGLE_CODE_INVALID:
                    $this->addError("google_code", UserModule::t("Invalid two-factor authentification code."));
                    break;
                case UserIdentity::ERROR_PASSWORD_INVALID:
                    $this->addError("password", UserModule::t("Password is incorrect."));
                    break;
                case UserIdentity::ERROR_INACTIVE:
                    $this->addError("username", UserModule::t("Account not active. Please activate your account."));
                    break;
            }
        }
    }

    public function login()
    {
        return Mindy::app()->auth->login($this->_identity->getModel(), $this->rememberMe ? null : 3600);
    }

    public function getUser()
    {
        return $this->_identity->getModel();
    }
}

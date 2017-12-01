<?php

namespace Modules\User\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\Fields\PasswordField;
use Mindy\Form\Fields\RecaptchaField;
use Mindy\Form\Form;
use Mindy\Locale\Translate;
use Mindy\Validation\MinLengthValidator;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class RegistrationForm
 * @package Modules\User
 */
class RegistrationForm extends Form
{
    public function getFields()
    {
        $fields = [
            /*'username' => [
                'class' => CharField::className(),
                'label' => UserModule::t('Username'),
                'required' => true,
                'validators' => [
                    function ($value) {
                        if (User::objects()->filter(['username' => $value])->count() > 0) {
                            return UserModule::t("Username must be a unique");
                        }
                        return true;
                    }
                ]
            ],*/
            'f_name' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => false,
                'validators' => [
                ],
                'html' => [
                    'placeholder' => UserModule::t('First name')
                ]
            ],
            'l_name' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => false,
                'validators' => [
                ],
                'html' => [
                    'placeholder' => UserModule::t('Last name')
                ]
            ],
            'email' => [
                'class' => EmailField::className(),
                'required' => true,
                'label' => false,
                'validators' => [
                    function ($value) {
                        if (User::objects()->filter(['email' => $value])->count() > 0) {
                            return UserModule::t("Email must be a unique");
                        }
                        return true;
                    }
                ],
                'html' => [
                    'placeholder' => UserModule::t('Email')
                ]
            ],
            'password' => [
                'class' => PasswordField::className(),
                'label' => false,
                'validators' => [
                    new MinLengthValidator(8)
                ],
                'html' => [
                    'placeholder' => UserModule::t('Password')
                ]
            ],
            'password_repeat' => [
                'class' => PasswordField::className(),
                'label' => false,
                'validators' => [
                    new MinLengthValidator(8)
                ],
                'html' => [
                    'placeholder' => UserModule::t('Password repeat')
                ]
            ],
            '2fa_enabled' => [
                'class' => CheckboxField::className(),
                'validators' => [
                ],
                'label' => UserModule::t('Enable 2 FA'),
            ],
            'not_us' => [
                'class' => CheckboxField::className(),
                'validators' => [
                    function ($value) {
                        if (!$value) {
                            return UserModule::t("US citizen cannot be registered!");
                        }
                        return true;
                    }
                ],
                'label' => UserModule::t('I am not a US resident')
            ],
            'accept_terms' => [
                'class' => CheckboxField::className(),
                'validators' => [
                    function ($value) {
                        if (!$value) {
                            return UserModule::t("Terms not accepted!");
                        }
                        return true;
                    }
                ],
                'label' => UserModule::t("Accept token sale <a class='terms' href='/'>terms and conditions</a>")
            ],
        ];

        $module = Mindy::app()->getModule('User');

        if ($module->enableRecaptcha) {
            if (empty($module->recaptchaPublicKey) && empty($module->recaptchaSecretKey)) {
                Mindy::app()->logger->warning("publicKey and secretKey isn't set in UserModule");
            } else {
                $fields['captcha'] = [
                    'class' => RecaptchaField::className(),
                    'label' => Translate::getInstance()->t('validation', 'Captcha'),
                    'publicKey' => $module->recaptchaPublicKey,
                    'secretKey' => $module->recaptchaSecretKey
                ];
            }
        }

        return $fields;
    }

    public function cleanPassword_repeat($value)
    {
        if ($this->password->getValue() === $value) {
            return $value;
        } else {
            $this->addError('password_repeat', 'Incorrect password repeat');
        }
        return null;
    }

    public function cleanEmail($value)
    {
        if (User::objects()->filter(['email' => strtolower($value)])->count() > 0) {
            $this->addError('email', UserModule::t('This email address is already in use on the site'));
        }
        return $value;
    }

    public function cleanUsername($value)
    {
        if (User::objects()->filter(['username' => $value])->count() > 0) {
            $this->addError('username', UserModule::t('This username is already in use on the site'));
        }
        return $value;
    }

    public function getModel()
    {
        return new User;
    }

    public function save()
    {
        $model = User::objects()->createUser($this->username->getValue(), $this->password->getValue(), $this->email->getValue());
        if ($model->hasErrors() === false) {
            return $model;
        }
        return false;
    }
}

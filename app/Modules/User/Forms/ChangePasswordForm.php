<?php

namespace Modules\User\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\PasswordField;
use Mindy\Form\Form;
use Mindy\Validation\MinLengthValidator;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class ChangePasswordForm
 * @package Modules\User
 */
class ChangePasswordForm extends Form
{
    /**
     * @var \Modules\User\Models\User
     */
    private $_model;

    public function init()
    {
        parent::init();

        $model = $this->getModel();
        if (empty($model->password)) {
            $this->exclude[] = 'password_current';
        }
    }

    /**
     * @param \Modules\User\Models\User
     */
    public function setModel(User $model)
    {
        $this->_model = $model;
    }

    /**
     * @return \Modules\User\Models\User
     */
    public function getModel()
    {
        return $this->_model;
    }

    public function getFields()
    {
        $fields = [
            'password_current' => [
                'class' => PasswordField::className(),
                'label' => UserModule::t('Old Password'),
                'html' => [
                    'placeholder' => UserModule::t('Old Password')
                ]
            ],
            'password_create' => [
                'class' => PasswordField::className(),
                'validators' => [
                    new MinLengthValidator(8)
                ],
                'label' => UserModule::t('New Password'),
                'html' => [
                    'placeholder' => UserModule::t('New password')
                ]
            ],
            'password_repeat' => [
                'class' => PasswordField::className(),
                'validators' => [
                    new MinLengthValidator(8)
                ],
                'label' => UserModule::t('Confirm Password'),
                'html' => [
                    'placeholder' => UserModule::t('Password Confirmation')
                ],
                'hint' => UserModule::t('Please repeat your password')
            ]
        ];

        if (empty($this->getModel()->password)) {
            unset($fields['password_current']);
        }

        return $fields;
    }

    public function cleanPassword_current($value)
    {
        $model = $this->getModel();
        $auth = Mindy::app()->auth;
        $hasher = $auth->getPasswordHasher($model->hash_type);
        if ($hasher->verifyPassword($this->password_current->getValue(), $model->password) === false) {
            $this->addError('password_current', 'Incorrect password');
            return null;
        }
        return $value;
    }

    public function cleanPassword_repeat($value)
    {
        if ($this->password_create->getValue() !== $value) {
            $this->addError('password_repeat', 'Incorrect password repeat');
            return null;
        }
        return $value;
    }

    public function save()
    {
        $model = $this->getModel();
        return $model->objects()->setPassword($this->password_create->getValue(), $model->hash_type);
    }
}

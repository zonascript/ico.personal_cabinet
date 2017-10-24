<?php

namespace Modules\User\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\PasswordField;
use Mindy\Form\Form;
use Mindy\Validation\MinLengthValidator;
use Modules\User\Models\User;
use Modules\User\UserModule;

/**
 * Class RecoverPasswordForm
 * @package Modules\User
 */
class RecoverPasswordForm extends Form
{
    /**
     * @var \Modules\User\Models\User
     */
    private $_model;

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
        return [
            'password_create' => [
                'class' => PasswordField::className(),
                'validators' => [
                    new MinLengthValidator(6)
                ],
                'label' => UserModule::t('Password')
            ],
            'password_repeat' => [
                'class' => PasswordField::className(),
                'validators' => [
                    new MinLengthValidator(6)
                ],
                'label' => UserModule::t('Password repeat'),
                'hint' => UserModule::t('Please repeat your password')
            ]
        ];
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

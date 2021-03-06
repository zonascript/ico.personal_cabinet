<?php

namespace Modules\User\Forms;


use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\ModelForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

class ProfileForm extends ModelForm
{

    public $exclude = [
        'username',
        'password',
        'activation_key',
        'is_active',
        'is_staff',
        'is_superuser',
        'last_login',
        'groups',
        'permissions',
        'hash_type',
        'key',
        'google_secret',
        'is_2fa',
        'created_at',
    ];

    public function getFields()
    {
        $fields = [
            'f_name' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => UserModule::t('First name'),
                'validators' => [
                ],
                'html' => [
                    'placeholder' => UserModule::t('First name')
                ]
            ],
            'l_name' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => UserModule::t('Last name'),
                'validators' => [
                ],
                'html' => [
                    'placeholder' => UserModule::t('Last name')
                ]
            ],
            'email' => [
                'class' => EmailField::className(),
                'required' => false,
                'label' => UserModule::t('Email'),
                'html' => [
                    'placeholder' => UserModule::t('Email'),
                    'disabled' => 'disabled'
                ]
            ],
        ];

        return $fields;
    }

    public function getModel()
    {
        return new User;
    }

    public function getExclude()
    {
        return parent::getExclude(); // TODO: Change the autogenerated stub
    }
}
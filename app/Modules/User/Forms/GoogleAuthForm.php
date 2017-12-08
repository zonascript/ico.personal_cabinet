<?php

namespace Modules\User\Forms;


use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\Form;
use Mindy\Form\ModelForm;
use Modules\User\Models\User;
use Modules\User\UserModule;

class GoogleAuthForm extends Form
{

    public function getFields()
    {
        $fields = [
            'google_code' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => UserModule::t('Google auth code'),
                'validators' => [
                ],
                'html' => [
                    'placeholder' => UserModule::t('Google auth code')
                ]
            ],
        ];

        return $fields;
    }

    public function getModel()
    {
        return new User;
    }

}
<?php

/**
 * User: max
 * Date: 31/08/15
 * Time: 14:18
 */

namespace Modules\Mail\Forms;

use Mindy\Form\Fields\AceField;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\DateTimeField;
use Mindy\Form\Fields\DropDownField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\Fields\TextField;
use Mindy\Form\ModelForm;
use Modules\Mail\Models\Mail;

class MailForm extends ModelForm
{
    public function getFields()
    {
        return [
            'queue' => [
                'class' => DropDownField::className(),
                'html' => [
                    'disabled' => true
                ]
            ],
            'email' => [
                'class' => EmailField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
            'subject' => [
                'class' => CharField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
            'message_txt' => [
                'class' => TextField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
            'message_html' => [
                'class' => AceField::className(),
                'readOnly' => true
            ],
            'error' => [
                'class' => TextField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
            'is_sended' => [
                'class' => CheckboxField::className(),
                'html' => [
                    'disabled' => true
                ]
            ],
            'is_read' => [
                'class' => CheckboxField::className(),
                'html' => [
                    'disabled' => true
                ]
            ],
            'readed_at' => [
                'class' => DateTimeField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'html' => [
                    'readonly' => true
                ]
            ]
        ];
    }

    public function getModel()
    {
        return new Mail;
    }
}

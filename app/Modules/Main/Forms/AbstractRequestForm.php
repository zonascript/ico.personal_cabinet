<?php
namespace Modules\Main\Forms;

use Modules\Main\MainModule;
use Xcart\App\Form\BaseForm;
use Xcart\App\Form\Fields\CharField;
use Xcart\App\Form\Fields\TextField;
use Xcart\App\Main\Xcart;
use Xcart\App\Validation\EmailValidator;

abstract class AbstractRequestForm extends BaseForm
{
    public $sendTo = 'helpdesc@s3stores.com';

    public function getFields()
    {
        return [
            'fullname' => [
                'class' => CharField::className(),
                'required' => true,
                'label' => MainModule::t('Your full name'),
                'hint' => MainModule::t('Your first and last name'),
                'html' => [
                    'placeholder' => MainModule::t('Albert H. Einstein'),
                ]
            ],
            'company' => [
                'class' => CharField::className(),
                'label' => MainModule::t('Your company name'),
                'html' => [
                    'placeholder' => MainModule::t('Eureka Inc.'),
                ]
            ],
            'phone' => [
                'class' => CharField::className(),
                'label' => MainModule::t('Your phone number'),
                'hint' => MainModule::t('Phone number you can be reached at'),
                'type' => 'tel',
                'html' => [
                    'placeholder' => '(609) 734-8000',
                ],
            ],
            'email' => [
                'class' => CharField::className(),
                'label' => MainModule::t('Your email address'),
                'hint' => MainModule::t('Valid email address is a must'),
                'type' => 'email',
                'required' => true,

                'validators' => [
                    new EmailValidator()
                ],
                'html' => [
                    'placeholder' => 'albert.einstein@gmail.com',
                ],
            ],
            'subject' => [
                'class' => CharField::className(),
                'label' => MainModule::t('Subject line'),
                'required' => true,
                'html' => [
                    'placeholder' => 'Is gravitation responsible for people falling in love?',
                ],
            ],
            'message' => [
                'class' => TextField::className(),
                'label' => MainModule::t('Your message'),
                'required' => true,
            ],
        ];
    }

    public function send()
    {
        return (bool)Xcart::app()->mail->template(
            $this->sendTo,
            $this->getField('subject')->getValue(),
            'mail/form_auto.tpl',
            ['form' => $this]
        );
    }
}
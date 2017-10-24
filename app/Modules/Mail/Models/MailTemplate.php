<?php

namespace Modules\Mail\Models;

use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\Mail\MailModule;

class MailTemplate extends Model
{
    public static function getFields()
    {
        return [
            'code' => [
                'class' => CharField::className(),
                'null' => false,
                'unique' => true,
                'verboseName' => MailModule::t('Code')
            ],
            'subject' => [
                'class' => CharField::className(),
                'null' => false,
                'verboseName' => MailModule::t('Subject')
            ],
            'template' => [
                'class' => TextField::className(),
                'null' => false,
                'verboseName' => MailModule::t('Template')
            ],
            'is_locked' => [
                'class' => BooleanField::className(),
                'default' => false,
                'verboseName' => MailModule::t('Is locked')
            ]
        ];
    }

    public function __toString()
    {
        return (string)$this->code;
    }
}

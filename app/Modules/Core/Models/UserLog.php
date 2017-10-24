<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 27/01/15 17:29
 */

namespace Modules\Core\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\IpField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\Core\CoreModule;
use Modules\User\Models\User;

class UserLog extends Model
{
    public $template = "{user} ({ip}): {message}";

    public static function getFields()
    {
        return [
            'user' => [
                'class' => ForeignField::className(),
                'null' => true,
                'modelClass' => User::className(),
                'verboseName' => CoreModule::t('User'),
            ],
            'ip' => [
                'class' => IpField::className(),
                'null' => false,
                'verboseName' => CoreModule::t('Ip address')
            ],
            'name' => [
                'class' => CharField::className(),
                'verboseName' => CoreModule::t('Message'),
                'null' => true
            ],
            'message' => [
                'class' => TextField::className(),
                'verboseName' => CoreModule::t('Message')
            ],
            'module' => [
                'class' => CharField::className(),
                'verboseName' => CoreModule::t('Module')
            ],
            'model' => [
                'class' => CharField::className(),
                'verboseName' => CoreModule::t('Model'),
                'null' => true
            ],
            'url' => [
                'class' => CharField::className(),
                'null' => true,
                'verboseName' => CoreModule::t('Url')
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'verboseName' => CoreModule::t('Created at')
            ]
        ];
    }

    public function __toString()
    {
        return (string)strtr($this->template, [
            '{module}' => $this->module,
            '{ip}' => $this->ip,
            '{user}' => $this->user,
            '{message}' => $this->message,
            '{model}' => $this->model,
            '{created_at}' => $this->created_at
        ]);
    }
}

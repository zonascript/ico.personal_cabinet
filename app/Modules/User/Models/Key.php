<?php

namespace Modules\User\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\User\UserModule;

/**
 * Class Key
 * @package Modules\User
 */
class Key extends Model
{
    public static function getFields()
    {
        return [
            'user' => [
                'class' => ForeignField::className(),
                'modelClass' => User::className(),
                'verboseName' => UserModule::t('User')
            ],
            'key' => [
                'class' => CharField::className(),
                'length' => 40,
                'verboseName' => UserModule::t("Key"),
                'unique' => true
            ],
        ];
    }

    public function __toString()
    {
        return (string)$this->key;
    }
}

<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/03/16 17:28
 */
namespace Modules\User\Models;

use Mindy\Orm\Fields\BooleanField;
use Modules\Core\Models\SettingsModel;
use Modules\User\UserModule;

class UserSettings extends SettingsModel
{
    public static function getFields() 
    {
        return [
            'registration' => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t('Registration enabled'),
                'default' => false
            ],
            'activation' => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t('Activation is needed'),
                'default' => false
            ],
            'recover' => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t('Recover password enabled'),
                'default' => false
            ],
        ];
    }
    
    public function __toString()
    {
        return (string) UserModule::t('User settings');
    }
}
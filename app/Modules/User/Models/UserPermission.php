<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 15/07/14.07.2014 19:22
 */

namespace Modules\User\Models;

use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\User\UserModule;

/**
 * Class UserPermission
 * @package Modules\User
 */
class UserPermission extends Model
{
    public static function getFields()
    {
        return [
            'user' => [
                'class' => ForeignField::className(),
                'modelClass' => User::className(),
                'verboseName' => UserModule::t("User"),
            ],
            'permission' => [
                'class' => ForeignField::className(),
                'modelClass' => Permission::className(),
                'verboseName' => UserModule::t("Permission"),
            ]
        ];
    }
}

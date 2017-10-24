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
 * @date 09/07/14.07.2014 17:29
 */

namespace Modules\User\Models;

use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;
use Modules\User\Components\Permissions;
use Modules\User\UserModule;

/**
 * Class PermissionObjectThrough
 * @package Modules\User
 */
class PermissionObjectThrough extends Model
{
    public static function getFields()
    {
        return [
            'owner_id' => [
                'class' => IntField::className(),
                'verboseName' => UserModule::t("Owner"),
            ],
            'type' => [
                'class' => IntField::className(),
                'choices' => [
                    Permissions::TYPE_USER,
                    Permissions::TYPE_GROUP,
                ],
                'verboseName' => UserModule::t("Type"),
            ],
            'permission' => [
                'class' => ForeignField::className(),
                'modelClass' => PermissionObject::className(),
                'verboseName' => UserModule::t("Permission"),
            ]
        ];
    }
}


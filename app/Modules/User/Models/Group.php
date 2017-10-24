<?php

namespace Modules\User\Models;

use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\User\UserModule;

/**
 * Class Group
 * @package Modules\User
 */
class Group extends Model
{
    public static function getFields()
    {
        return [
            "name" => [
                'class' => CharField::className(),
                'verboseName' => UserModule::t("Name"),
            ],
            "description" => [
                'class' => TextField::className(),
                'verboseName' => UserModule::t("Description"),
                'null' => true
            ],
            "is_locked" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is locked"),
            ],
            "is_visible" => [
                'class' => BooleanField::className(),
                'default' => true,
                'verboseName' => UserModule::t("Is visible"),
            ],
            "is_default" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is default"),
            ],
            'permissions' => [
                'class' => ManyToManyField::className(),
                'modelClass' => Permission::className(),
                'through' => GroupPermission::className(),
                'verboseName' => UserModule::t("Permissions"),
            ],
            'users' => [
                'class' => ManyToManyField::className(),
                'modelClass' => User::className(),
                'verboseName' => UserModule::t("Users"),
                'editable' => false
            ]
        ];
    }

    public function __toString()
    {
        return (string)$this->name;
    }
}

//class UserGroup extends MActiveRecord
//{
//    public function behaviors()
//    {
//        return array(
//            'MPermissionBehavior' => array(
//                'class' => 'user.behaviors.MPermissionBehavior',
//                'type' => MPermissionManager::TYPE_GROUP
//            )
//        );
//    }
//}

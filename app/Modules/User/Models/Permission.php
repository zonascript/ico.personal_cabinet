<?php

namespace Modules\User\Models;

use Mindy\Base\Mindy;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;
use Modules\User\UserModule;

/**
 * Class Permission
 * @package Modules\User
 */
class Permission extends Model
{
    const TYPE_USER = 0;
    const TYPE_GROUP = 1;

    public function __toString()
    {
        return (string) $this->code;
    }

    public static function getFields()
    {
        return [
            "code" => [
                'class' => CharField::className(),
                'verboseName' => UserModule::t("Key"),
                'unique' => true,
                'helpText' => UserModule::t("Rule code for developers to use in source code")
            ],
            "name" => [
                'class' => CharField::className(),
                'verboseName' => UserModule::t("Name"),
                'helpText' => UserModule::t("Rule name")
            ],
            "bizrule" => [
                'class' => CharField::className(),
                'null' => true,
                'verboseName' => UserModule::t("Bussines rule"),
                'helpText' => UserModule::t("More info in documentation")
            ],
            "is_locked" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is locked"),
                'helpText' => UserModule::t("Locked for editing. Editing allowed only for super-administrator.")
            ],
            "is_auto" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is auto"),
                'helpText' => UserModule::t("Rule created automatically by module. Editing allowed only for super-administrator.")
            ],
            "is_visible" => [
                'class' => BooleanField::className(),
                'default' => true,
                'verboseName' => UserModule::t("Is visible"),
                'helpText' => UserModule::t('Rule is visible only to super-administrator')
            ],
            "is_default" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is default"),
                'helpText' => UserModule::t("Default rule. Rule applies to all users after creation")
            ],
            "is_global" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is global"),
                'helpText' => UserModule::t("Global rule. This type of rule has priority and overrides other rules. More info in documentation.")
            ],
        ];
    }

    public function getTypes()
    {
        return [
            self::TYPE_USER => UserModule::t('User'),
            self::TYPE_GROUP => UserModule::t('Group'),
        ];
    }

    /**
     * @return \Modules\User\Components\Permissions
     */
    protected function getPermissions()
    {
        return Mindy::app()->getComponent('permissions');
    }
}

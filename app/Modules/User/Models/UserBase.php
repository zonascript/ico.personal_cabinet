<?php

namespace Modules\User\Models;

use Mindy\Base\Mindy;
use Mindy\Helper\Params;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\EmailField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\PasswordField;
use Mindy\Orm\Model;
use Mindy\Validation\UniqueValidator;
use Modules\User\Components\AuthTrait;
use Modules\User\Components\PermissionTrait;
use Modules\User\UserModule;

/**
 * Class UserBase
 * @package Modules\User
 * @method static \Modules\User\Models\UserManager objects($instance = null)
 */
abstract class UserBase extends Model
{
    use PermissionTrait, AuthTrait;

    const GUEST_ID = -1;

    public static function getFields()
    {
        return [
            "username" => [
                'class' => CharField::className(),
                'verboseName' => UserModule::t("Username"),
                'unique' => true
            ],
            "email" => [
                'class' => EmailField::className(),
                'verboseName' => UserModule::t("Email"),
                'null' => true,
            ],
            "password" => [
                'class' => PasswordField::className(),
                'null' => true,
                'verboseName' => UserModule::t("Password"),
            ],
            "activation_key" => [
                'class' => CharField::className(),
                'null' => true,
                'verboseName' => UserModule::t("Activation key"),
            ],
            "is_active" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is active"),
            ],
            "is_staff" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is staff"),
            ],
            "is_superuser" => [
                'class' => BooleanField::className(),
                'verboseName' => UserModule::t("Is superuser"),
            ],
            'last_login' => [
                'class' => IntField::className(),
                'null' => true,
                'verboseName' => UserModule::t("Last login"),
            ],
            'groups' => [
                'class' => ManyToManyField::className(),
                'modelClass' => Group::className(),
                'verboseName' => UserModule::t("Groups"),
            ],
            'permissions' => [
                'class' => ManyToManyField::className(),
                'modelClass' => Permission::className(),
                'through' => UserPermission::className(),
                'verboseName' => UserModule::t("Permissions"),
            ],
            'hash_type' => [
                'class' => CharField::className(),
                'default' => 'mindy',
                'editable' => false,
                'verboseName' => UserModule::t("Password hash strategy"),
            ],
            'key' => [
                'class' => ForeignField::className(),
                'modelClass' => Key::className(),
                'null' => true,
                'verboseName' => UserModule::t("User profile"),
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'verboseName' => UserModule::t('Created at')
            ]
        ];
    }

    public function getIp()
    {
        return Mindy::app()->request->http->getUserHostAddress();
    }

    public function __toString()
    {
        return (string)$this->username;
    }

    public function getPermissionList()
    {
        return Mindy::app()->authManager->getPermIdArray();
    }

    /**
     * @param null $instance
     * @return \Mindy\Orm\Manager|UserManager
     */
    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        return new UserManager($instance ? $instance : new $className);
    }

    public function beforeSave($owner, $isNew)
    {
        if ($isNew) {
            $owner->activation_key = substr(md5(time() . $owner->username . $owner->pk), 0, 10);
        }
    }
}

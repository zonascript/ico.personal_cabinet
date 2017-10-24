<?php

namespace Modules\User\Components;

use Mindy\Base\Mindy;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Modules\User\Models\GroupPermission;
use Modules\User\Models\Permission;
use Modules\User\Models\User;
use Modules\User\Models\UserPermission;

/**
 * Class Permissions
 * @package Modules\User
 */
class Permissions
{
    use Accessors, Configurator;

    /**
     * Автоматически созданные права доступа.
     * Данный тип прав доступа не возможно редактировать из панели управления,
     * они изменяются только при установке / удалении / обновлении модулей или системы.
     */
    const IS_AUTO_PERMISSION = 1;
    /**
     * Заблокированные права доступа.
     * Это права доступа которые не подлежат изменениям в связи с логикой проекта. Редактируется пользователем
     * из панели управления только в случае если права не являются автоматическими.
     * {see: MPermissionManager::IS_AUTO_PERMISSION}
     */
    const IS_LOCKED_PERMISSION = 1;
    /**
     * Глобальные права доступа. Подробнее: {@link MPermissionManager::canGlobal}
     */
    const IS_GLOBAL_PERMISSION = 1;
    /**
     * Права до action контроллера
     */
    const TYPE_ACTION = 0;
    /**
     * Права до модели
     */
    const TYPE_MODEL = 1;
    /**
     * Тип владельца в таблице перелинковки прав доступа: пользователь
     */
    const TYPE_USER = 1;
    /**
     * Тип владельца в таблице перелинковки прав доступа: группа
     */
    const TYPE_GROUP = 2;
    /**
     * Разрешение на просмотр объекта
     */
    const CAN_VIEW = 1;
    /**
     * Разрешение на просмотр и редактирование
     */
    const CAN_VIEW_UPDATE = 2;
    /**
     * Разрешение на просмотр и удаление;
     */
    const CAN_VIEW_DELETE = 3;
    /**
     * Разрешение на редактирование объекта
     */
    const CAN_UPDATE = 4;
    /**
     * Разрешение на редактирование и удаление объекта
     */
    const CAN_UPDATE_DELETE = 5;
    /**
     * Разрешение на удаление объекта
     */
    const CAN_DELETE = 6;
    /**
     * Запрет на какое либо из действий с объектом
     */
    const FORBIDDEN = 7;
    /**
     * @var array вида permission_code => bizRule
     */
    private $_permissions = [];
    /**
     * @var array вида permission_code => array с ids групп
     */
    private $_groupPerms = [];
    /**
     * @var array вида permission_code => array с ids пользователей
     */
    private $_userPerms = [];
    /**
     * @var array вида permission_code => array с ids моделей => array с ids групп
     */
    private $_groupPermsObjects = [];
    /**
     * @var array вида permission_code => array с ids моделей => array с ids пользователей
     */
    private $_userPermsObjects = [];
    /**
     * @bool Отображение ошибок выполнения bizRule
     */
    public $showErrors = MINDY_DEBUG;
    /**
     * @var bool Auto fetch data from database
     */
    public $autoFetch = true;

    /**
     * Инициализация компонента, получение всех прав доступа
     */
    public function init()
    {
        if ($this->autoFetch) {
            $this->fetchData();
        }
    }

    public function fetchData()
    {
        $this->getInitialData();
        $this->getInitialObjectsData();
    }

    /**
     * Получение данных по правам доступа, по правам групп и по пользователей.
     */
    public function getInitialData()
    {
        $dbPermissions = Permission::objects()->asArray()->all();

        $permissionsCount = count($dbPermissions);
        for ($i = 0; $i < $permissionsCount; $i++) {
            $this->_permissions[$dbPermissions[$i]['code']] = array(
                'bizrule' => $dbPermissions[$i]['bizrule'],
                'name' => $dbPermissions[$i]['name'],
                'id' => $dbPermissions[$i]['id'],
                'is_global' => $dbPermissions[$i]['is_global'],
            );
        }

        $userPerms = UserPermission::objects()->filter(['permission__code__isnull' => false])->all();
        foreach ($userPerms as $perm) {
            $code = $perm->permission->code;
            $this->_userPerms[$code][] = $perm->user_id;
        }

        $groupPerms = GroupPermission::objects()->filter(['permission__code__isnull' => false])->all();
        foreach ($groupPerms as $perm) {
            $code = $perm->permission->code;
            $this->_groupPerms[$code][] = $perm->group_id;
        }
    }

    /**
     * Получение данных по правам объектов.
     */
    public function getInitialObjectsData()
    {
//        $permThroughs = PermissionObjectThrough::objects()->all();
//        foreach($permThroughs as $perm) {
//            $code = $perm->permission->code;
//            if($perm->type == self::TYPE_USER) {
//                $this->_userPermsObjects[$code][$perm->model_id][] = $perm->owner_id;
//            } else {
//                $this->_groupPermsObjects[$code][$perm->model_id][] = $perm->owner_id;
//            }
//        }
    }

    /**
     * Проверка прав доступа на группу пользователя по переданному коду.
     * Проверяем существуют ли такой код прав доступа и есть ли pk групп пользвователя в разрешенных
     * По умолчанию все действия на группу запрещены
     *
     * @param $code
     * @param $userId
     * @param null $type
     * @return bool
     */
    public function hasGroupPermission($code, $userId, $type = null)
    {
        if (isset($this->_groupPerms[$code]) && !empty($this->_groupPerms[$code])) {
            $groups = $this->getUserGroups($userId);
            foreach ($groups as $id) {
                if (in_array($id, $this->_groupPerms[$code])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверка прав доступа по коду на переданного пользователя
     * @param $code
     * @param $userId
     * @param null $type
     * @return bool
     */
    public function hasUserPermission($code, $userId, $type = null)
    {
        /*
         * По умолчанию все действия пользователю запрещены
         * Проверяем сущесвуют ли такой код прав доступа и есть ли pk пользователя в разрешенных
         */
        return isset($this->_userPerms[$code]) && in_array($userId, $this->_userPerms[$code]);
    }

    /**
     * Проверка прав доступа на группу пользователя по переданному коду до конкретного объекта.
     * @param $code
     * @param $modelId
     * @param $userId
     * @param null $type
     * @return bool
     */
    public function hasObjectGroupPermission($code, $modelId, $userId, $type = null)
    {
        /*
         * По умолчанию все действия на группу запрещены
         * Проверяем сущесвуют ли такой код прав доступа и есть ли pk групп пользвователя в разрешенных
         */
        if (isset($this->_groupPermsObjects[$code]) && isset($this->_groupPermsObjects[$code][$modelId])) {
            $groups = $this->getUserGroups($userId);
            foreach ($groups as $id => $name) {
                if (in_array($id, $this->_groupPermsObjects[$code][$modelId])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверка прав доступа по коду на переданного пользователя  до конкретного объекта.
     * @param $code
     * @param $userId
     * @param $modelId
     * @return bool
     */
    public function hasObjectUserPermission($code, $modelId, $userId)
    {
        /*
         * По умолчанию все действия пользователю запрещены
         * Проверяем сущесвуют ли такой код прав доступа и есть ли pk пользователя в разрешенных
         */
        return isset($this->_userPermsObjects[$code]) &&
        isset($this->_userPermsObjects[$code][$modelId]) &&
        in_array($userId, $this->_userPermsObjects[$code][$modelId]);
    }

    /**
     * Проверка прав доступа по переданному массиву кодов прав доступа
     * @param $codeArray
     * @param $userId
     * @param array $params
     * @param null $type
     * @return bool
     */
    protected function canArray($codeArray, $userId, $params = [], $type = null)
    {
        foreach ($codeArray as $code) {
            if (isset($this->_permissions[$code]) && $this->can($code, $userId, $params, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка прав доступа по переданному массиву кодов прав доступа
     * @param $codeArray
     * @param $modelId
     * @param $userId
     * @param array $params
     * @param null $type
     * @return bool
     */
    protected function canArrayObject($codeArray, $modelId, $userId, $params = [], $type = null)
    {
        foreach ($codeArray as $code) {
            if (isset($this->_permissions[$code]) && $this->canObject($code, $modelId, $userId, $params, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка глобально разрешенных прав доступа в обход всех остальных проверок.
     * При проверке не учитываются: пользователь, группа, персонал {@link User::IS_STAFF} и супер пользователь
     * {@link User::IS_SUPERUSER}
     * @param $code
     * @return bool
     */
    public function canGlobal($code)
    {
        return isset($this->_permissions[$code]) && (int)$this->_permissions[$code]['is_global'] === self::IS_GLOBAL_PERMISSION;
    }

    /**
     * Проверка прав доступа до конечного объекта
     * @param $code string код прав доступа
     * @param $modelId int pk объекта
     * @param $userId int pk пользователя
     * @param array $params параметры для бизнес правил
     * @return bool
     */
    public function canObject($code, $modelId, $userId, $params = [], $type = null)
    {
        if (is_array($code)) {
            return $this->canArrayObject($code, $modelId, $userId, $params, $type);
        } else {
            /**
             * Проверяем глобально разрешенные действия без учета bizRule
             */
            if ($this->canGlobal($code)) {
                return true;
            }

            /**
             * Проверяем права доступа текущего пользователя
             */
            $userCan = $this->canUserObject($code, $modelId, $userId, $params, $type);

            /**
             * Если пользователю запрещено или у пользователя отсутствовали такой код прав доступа,
             * проверяем права группы и выполняем бизнес правило
             * Иначе сразу возвращаем результат чтобы не запрашивать права доступа
             * и выполняем бизнес правило
             */
            if ($userCan === false) {
                return $this->canGroupObject($code, $modelId, $userId, $params, $type);
            }

            return $userCan;
        }
    }

    private function canUser($code, $userId, $params, $type = null)
    {
        return $this->hasUserPermission($code, $userId, $type) === true && $this->canBizRule($code, $params) === true;
    }

    private function canGroup($code, $userId, $params, $type = null)
    {
        return $this->hasGroupPermission($code, $userId, $type) === true && $this->canBizRule($code, $params) === true;
    }

    private function canUserObject($code, $modelId, $userId, $params, $type = null)
    {
        return $this->hasObjectUserPermission($code, $modelId, $userId, $type) === true && $this->canBizRule($code, $params) === true;
    }

    private function canGroupObject($code, $modelId, $userId, $params, $type = null)
    {
        return $this->hasObjectGroupPermission($code, $modelId, $userId, $type) === true && $this->canBizRule($code, $params) === true;
    }

    public function create($code, $name = null, $module = null)
    {
        $data = [];
        if ($module !== null) {
            $data = array(
                'is_locked' => self::IS_LOCKED_PERMISSION,
                'is_auto' => self::IS_AUTO_PERMISSION,
                'module' => $module
            );
        }

        $data['code'] = $code;

        if ($name !== null) {
            $data['name'] = $name;
        }

        return Mindy::app()->db->createCommand()->insert($this->tablePermission, $data);
    }

    /**
     * Назначение разрешения прав доступа на пользователя или группу пользователя по переданному коду
     * прав доступа и типу пользователя или группы пользователя
     * @param $code
     * @param $ownerId
     * @param $type
     * @return bool
     */
    public function set($code, $ownerId, $type)
    {
        if ($this->isExistPermission($code, $ownerId, $type)) {
            return true;
        }

        // Если кода прав доступа не существует возвращаем ложь
        if (isset($this->_permissions[$code]) === false) {
            return false;
        }

        // Присваиваем права доступа
        $setPermCommand = Mindy::app()->db->createCommand()->insert($this->tablePermissionLink, array(
            'owner_id' => (int)$ownerId,
            'permission_id' => $this->_permissions[$code]['id'],
            'type' => $type
        ));

        return $setPermCommand > 0;
    }

    /**
     * Функция хелпер для установки прав доступа по pk на владельца по переданному типу
     * @param $permId
     * @param $ownerId
     * @param $type
     */
    public function setId($permId, $ownerId, $type)
    {
        // Проверяем корректность переданного типа
        if (in_array($type, array(self::TYPE_USER, self::TYPE_GROUP)) === false) {
            return false;
        }

        // Находим код прав доступа
        $model = Permission::objects()->filter(['pk' => $permId])->get();
        if ($model === null) {
            return false;
        }

        return $this->set($model->code, $ownerId, $type);
    }

    /**
     * Права доступа на редактирование объекта
     * @param $object
     * @return bool
     */
    public function isCanUpdate($object)
    {
        return $this->hasPerObjectPermission($object, self::CAN_UPDATE);
    }

    /**
     * Права доступа на удаление объекта
     * @param $model
     * @return bool
     */
    public function isCanDelete($object)
    {
        return $this->hasPerObjectPermission($object, self::CAN_DELETE);
    }

    /**
     * Права доступа на просмотр объекта
     * @param $model
     * @return bool
     */
    public function isCanView($object)
    {
        return $this->hasPerObjectPermission($object, self::CAN_VIEW);
    }

    /**
     * Запрещено ли пользователю выполнять какие либо действия с объектом
     * @param $model
     * @return bool
     */
    public function isForbidden($object)
    {
        return $this->hasPerObjectPermission($object, self::FORBIDDEN);
    }

    /**
     * Права доступа до объекта. У данных прав доступа наивысший приоритет
     *
     * @param $object
     * @param $action
     * @return bool
     */
    private function hasPerObjectPermission($object, $permission)
    {
        $pk = $object->{$object->tableSchema->primaryKey};
        $objectClassName = get_class($object);

        $db = Mindy::app()->db->createCommand();

        $canUser = $db->select('permission')
                ->from($this->tablePerObjectPermission)
                ->where('permission=:permission AND model_id=:pk AND model_class=:class AND type=:type', array(
                    'permission' => $permission,
                    'pk' => $pk,
                    'class' => $objectClassName,
                    'type' => self::TYPE_USER
                ))->count() > 0;

        if ($canUser === true) {
            return true;
        } else {
            $groupPerObjectPermissions = $db->select('id')
                ->from($this->tablePerObjectPermission)
                ->where(array('in', 'owner_id', $this->getUserGroups()))
                ->where('permission=:permission AND model_id=:pk AND model_class=:class AND type=:type', array(
                    'permission' => $permission,
                    'pk' => $pk,
                    'class' => $objectClassName,
                    'type' => self::TYPE_GROUP
                ))
                ->queryColumn();
            return count($groupPerObjectPermissions) > 0;
        }
    }

    public function isExistsPermissionCode($code)
    {
        $count = Mindy::app()->db->createCommand()
            ->select("COUNT(*)")
            ->from($this->tablePermission)
            ->where("code=:code", array('code' => $code))
            ->queryScalar();
        return (int)$count > 0;
    }

    /**
     * Проверка прав доступа на существование
     * @param $code
     * @param $ownerId
     * @param $type
     * @return int
     */
    public function isExistPermission($code, $ownerId, $type)
    {
        $count = Mindy::app()->db->createCommand()
            ->select('COUNT(*)')
            ->from($this->tablePermissionLink . " permLink")
            ->join($this->tablePermission . " perm", "perm.id=permLink.permission_id", [])
            ->where("permLink.type=:type AND permLink.owner_id=:owner_id AND perm.code=:code",
                array(
                    'owner_id' => $ownerId,
                    'type' => $type,
                    'code' => $code))
            ->queryScalar();

        return (int)$count;
    }

    public function setArray(array $permIds, $ownerId, $type, $clear = true)
    {
        if ($clear === true) {
            $this->clearAll($ownerId, $type);
        }

        foreach ($permIds as $permId) {
            $this->setId($permId, $ownerId, $type);
        }
    }

    /**
     * @return array Права доступа массивом для использования в формах вида code => name
     */
    public function getPermCodeArray()
    {
        $permissions = [];

        foreach ($this->_permissions as $code => $data) {
            $permissions[$code] = $data['name'];
        }

        return $permissions;
    }

    /**
     * @return array Права доступа массивом для использования в формах вида id => name
     */
    public function getPermIdArray()
    {
        $permissions = [];

        foreach ($this->_permissions as $code => $data) {
            if ($data['module'] === null && Mindy::app()->hasModule($data['module'])) {
                $name = Mindy::t(ucfirst($data['module']) . "Module.main", $data['name']);
            } else if (empty($data['name'])) {
                $name = $code;
            } else {
                $name = $data['name'];
            }

            $permissions[$data['id']] = $name;
        }

        return $permissions;
    }

    /**
     * Очистка всех прав доступа
     * @return mixed
     */
    public function clear()
    {
        return Mindy::app()->db->createCommand()->truncateTable($this->tablePermissionLink);
    }

    /**
     * Возвращает плоский массив групп пользователя вида:
     * [
     *    0 => 34,
     *    1 => 56
     * ]
     * @param null $userId
     * @return array user groups
     */
    public function getUserGroups($userId = null)
    {
        $user = Mindy::app()->getUser();
        if ($userId === null || $userId == $user->pk)
            $groups = $user->groups->valuesList(['id'], true);
        else {
            $user = User::objects()->filter(['pk' => $userId]);
            $groups = $user->groups->valuesList(['id'], true);
        }
        return $groups;
    }

    public function canBizRule($code, $params)
    {
        return $this->executeBizRule($this->_permissions[$code]['bizrule'], $params);
    }

    /**
     * Executes the specified business rule.
     * @param string $bizRule the business rule to be executed.
     * @param array $params parameters passed to {@link IAuthManager::checkAccess}.
     * @return boolean whether the business rule returns true.
     * If the business rule is empty, it will still return true.
     */
    public function executeBizRule($bizRule, array $params = [])
    {
        if ($bizRule === '' || $bizRule === null) {
            return true;
        } else {
            if (strpos($bizRule, 'return ') === false) {
                $bizRule = "return " . $bizRule;
            }
            $bizRule = 'extract($params);' . $bizRule;
            if (substr($bizRule, -1) !== ';') {
                $bizRule .= ';';
            }
            return $this->showErrors ? eval($bizRule) != 0 : @eval($bizRule) != 0;
        }
    }

    /**
     * Возвращаем полученные права по пользователям
     * @return array
     */
    public function getUserPerms()
    {
        return $this->_userPerms;
    }

    /**
     * Возвращаем полученные права по группам пользователей
     * @return array
     */
    public function getGroupPerms()
    {
        return $this->_groupPerms;
    }

    /**
     * Возвращаем полученные бизнес правила
     * @return array
     */
    public function getBizRules()
    {
        return $this->_permissions;
    }

    /**
     * Заглушка для совместимости со стандартным поведением Yii
     * {@link MPermissionManager::can}
     */
    public function checkAccess($code, $userId, $params = [])
    {
        return $this->can($code, $userId, $params);
    }

    /*
     * @DEPRECATED функция устарела. См MPermissionManager::setId
     * {@link MPermissionManager::setId}
     */
    public function setPermId($permId, $ownerId, $type)
    {
        return $this->set($permId, $ownerId, $type);
    }

    /*
     * @DEPRECATED функция устарела. См MPermissionManager::set
     * {@link MPermissionManager::set}
     */
    public function setPerm($code, $ownerId, $type)
    {
        return $this->set($code, $ownerId, $type);
    }

    /**
     * Проверка прав доступа
     * @param $code string код прав доступа
     * @param $userId int pk пользователя
     * @param array $params параметры для бизнес правил
     * @return bool
     */
    public function can($code, $userId, $params = [], $type = null)
    {
        if (is_array($code)) {
            return $this->canArray($code, $userId, $params, $type);
        } else {
            /**
             * Проверяем глобально разрешенные действия без учета bizRule
             */
            if ($this->canGlobal($code)) {
                return true;
            }

            /**
             * Проверяем права доступа текущего пользователя
             */
            $userCan = $this->canUser($code, $userId, $params, $type);

            /**
             * Если пользователю запрещено или у пользователя отсутствовали такой код прав доступа,
             * проверяем права группы и выполняем бизнес правило
             * Иначе сразу возвращаем результат чтобы не запрашивать права доступа
             * и выполняем бизнес правило
             */
            if ($userCan === false) {
                return $this->canGroup($code, $userId, $params, $type);
            }

            return $userCan;
        }
    }

    public function delete($code)
    {
        if (isset($this->_permissions[$code])) {
            unset($this->_permissions[$code]);
        }
    }
}

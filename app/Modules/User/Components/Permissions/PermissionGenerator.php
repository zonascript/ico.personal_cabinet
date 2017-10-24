<?php

namespace Modules\User\Components\Permissions;

use Exception;
use Mindy\Base\Generator;
use Mindy\Base\Mindy;

/**
 * Class PermissionGenerator
 * @package Modules\User
 */
class PermissionGenerator extends Generator
{
    /**
     * Получаем actions всей системы и переформируем массив к виду 0 => 'perm.code'
     */
    public function getActionsData()
    {
        $result = [];
        $data = $this->getControllerActions();

        if (isset($data['controllers'])) {
            $result = $this->getControllersActions($data['controllers']);
        }

        if (isset($data['modules'])) {
            $result = array_merge($result, $this->getModulesActions($data['modules']));
        }

        return $result;
    }

    /**
     * Получаем все действия (actions) переданных контроллеров. Если передано $module то добавляем его
     * к именованию конечного массива.
     * @param $controllers
     * @param null $module
     * @return array
     */
    protected function getControllersActions($controllers, $module = null)
    {
        $result = [];
        foreach ($controllers as $controller => $data) {
            if ($module !== null) {
                $controller = $module . '.' . $controller;
            }

            $result[$controller . '.*'] = array('module' => $module);

            foreach ($data['actions'] as $action => $methods) {
                $result[$controller . '.' . $action] = array('module' => $module);
            }
        }
        return $result;
    }

    /**
     * Получаем все модули, контроллеры и действия (actions) системы.
     * @param $modules
     * @param array $result
     * @return array
     */
    protected function getModulesActions($modules, $result = [])
    {
        foreach ($modules as $moduleName => $moduleData) {
            $result = array_merge($result, $this->getControllersActions($moduleData['controllers'], $moduleName));

            if (isset($moduleData['modules'])) {
                $result = array_merge($result, $this->getModulesActions($moduleData['modules']));
            }
        }

        return $result;
    }

    /**
     * TODO refactoring
     * Runs the generator.
     * @return bool the items generated or false if failed.
     */
    public function run()
    {
        $authManager = Mindy::app()->getAuthManager();
        $db = Mindy::app()->db;

        // Старт транзакции
        $transaction = $db->beginTransaction();
        $actions = $this->getActionsData();

        try {
            $generatedItems = array();

            foreach ($actions as $code => $data) {

                // Проверяем отсутствует ли правило в бд и нужно ли его добавлять
                if ($authManager->isExistsPermissionCode($code) === false) {

                    // Добавляем правило
                    $db->createCommand()->insert($authManager->tablePermission, array(
                        'code' => $code,
                        'module' => isset($data['module']) ? $data['module'] : new CDbExpression("NULL"),
                        'is_auto' => PermissionManager::IS_AUTO_PERMISSION,
                        'is_locked' => PermissionManager::IS_LOCKED_PERMISSION,
                    ));

                    $generatedItems[] = $code;
                }
            }

            $transaction->commit();
            return $generatedItems;
        } catch (Exception $e) {
            // Something went wrong, rollback
            $transaction->rollback();
            return false;
        }
    }
}

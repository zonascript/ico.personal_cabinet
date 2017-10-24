<?php

namespace Modules\User\Components;

use Mindy\Base\Mindy;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 24/04/14.04.2014 22:01
 */

/**
 * Class PermissionTrait
 * @package Modules\User
 */
trait PermissionTrait
{
    /**
     * @var array for cached permissions
     */
    private $_access = [];

    /**
     * Performs access check for this user.
     * Overloads the parent method in order to allow superusers access implicitly.
     * @param string $operation the name of the operation that need access check.
     * @param array $params name-value pairs that would be passed to business rules associated
     * with the tasks and roles assigned to the user.
     * @param boolean $allowCaching whether to allow caching the result of access checki.
     * This parameter has been available since version 1.0.5. When this parameter
     * is true (default), if the access check of an operation was performed before,
     * its result will be directly returned when calling this method to check the same operation.
     * If this parameter is false, this method will always call {@link CAuthManager::checkAccess}
     * to obtain the up-to-date access result. Note that this caching is effective
     * only within the same request.
     * @param null $type
     * @return boolean whether the operations can be performed by this user.
     */
    public function can($operation, $params = [], $allowCaching = true, $type = null)
    {
        $app = Mindy::app();

        $cache = $app->cache;

        /**
         * Пользователю суперадминистратор все разрешено по умолчанию
         */
        if ($this->is_superuser) {
            return true;
        }

        $cacheId = (is_array($operation) ? implode('|', $operation) : $operation) . $this->pk . '_' . count($params);

        /**
         * Проверяем данную операцию для пользователя в кеше
         */
        if (!$allowCaching || $cache->get($cacheId) === false) {
            $access = $app->permissions->can($operation, $this->pk, $params, $type);
            if ($allowCaching) {
                $cache->set($cacheId, (int)$access, 60 * 60 * 10);
            }
        } else {
            $access = (bool)$cache->get($cacheId);
        }

        return $this->_access[$cacheId] = $access;
    }

    /**
     * @param $operation
     * @param $modelId
     * @param array $params
     * @param bool $allowCaching
     * @param null $type
     * @return bool
     */
    public function canObject($operation, $modelId, $params = [], $allowCaching = true, $type = null)
    {
        $app = Mindy::app();

        $cache = $app->cache;

        // Пользователю суперадминистратор все разрешено по умолчанию
        if ($this->is_superuser) {
            return true;
        }

        $cacheId = (is_array($operation) ? implode('|', $operation) : $operation) . $modelId . $this->pk . '_' . count($params);

        /**
         * Проверяем данную операцию для пользователя в кеше
         */
        if (!$allowCaching || $cache->get($cacheId) === false) {
            $access = $app->permissions->canObject($operation, $modelId, $this->pk, $params);
            if ($allowCaching) {
                $cache->set($cacheId, (int)$access, 60 * 60 * 10);
            }
        } else {
            $access = (bool)$cache->get($cacheId);
        }

        return $this->_access[$cacheId] = $access;
    }
}

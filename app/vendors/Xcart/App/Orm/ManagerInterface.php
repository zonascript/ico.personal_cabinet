<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 19:14
 */

namespace Xcart\App\Orm;

/**
 * Interface ManagerInterface
 * @package Xcart\App\Orm
 */
interface ManagerInterface extends QuerySetInterface
{
    /**
     * @return ModelInterface
     */
    public function getModel();

    /**
     * @return \Xcart\App\Orm\QuerySet
     */
    public function getQuerySet();
}
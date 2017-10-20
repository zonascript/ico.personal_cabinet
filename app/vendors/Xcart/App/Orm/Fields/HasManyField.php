<?php

namespace Xcart\App\Orm\Fields;

use Exception;
use Xcart\App\Orm\Exception\OrmExceptions;
use Xcart\App\Orm\Manager;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class HasManyField
 * @package Xcart\App\Orm
 */
class HasManyField extends RelatedField
{
    /**
     * @var array extra condition for join
     */
    public $extra = [];
    /**
     * @var \Xcart\App\Orm\Model
     */
    protected $_relatedModel;

    /**
     * @var \Xcart\App\Orm\Model
     */
    protected $_model;

    public $modelClass;

    public $through;
    /**
     * @var array
     */
    public $link = [];

    public $null = true;

    public function getSqlType()
    {
        return false;
    }

    /**
     * @return \Xcart\App\Orm\ManagerInterface
     * @throws \Exception
     */
    public function getManager()
    {
        $where = [];
        if ($this->link) {
            foreach ($this->link as $from => $to) {
                $where[$from] = $this->getModel()->getAttribute($to);
            }
        }

        $manager = new Manager($this->getRelatedModel(), $this->getModel()->getConnection());
        $manager->filter(array_merge($where, $this->extra));

        if ($this->getModel()->getIsNewRecord()) {
            $manager->distinct();
        }

        return $manager;
    }

    public function setValue($value)
    {
        throw new Exception("Has many field can't set values. You can do it through ForeignKey.");
    }

    public function getJoin(QueryBuilder $qb, $topAlias)
    {
        $tableName = $this->getRelatedTable();
        $alias = $qb->makeAliasKey($tableName);
        $on = [];

        if ($this->link) {
            foreach ($this->link as $from => $to) {
                $on[$topAlias . '.' . $from] = $alias . '.' . $to;
            }
        }
        else {
            if (count($this->getRelatedModel()->getPrimaryKeyName(true)) == 1)
            {
                $on = [$topAlias . '.' . $this->getAttributeName() => $alias . '.' . $this->getRelatedModel()->getPrimaryKeyName()];
            }
            else {
                OrmExceptions::FailCreateLink();
            }
        }

        return [
            ['LEFT JOIN', $tableName, $on, $alias]
        ];
    }


    public function fetch($value)
    {
        return;
    }

    public function onBeforeDelete()
    {
        // @TODO: Добавить функциол. Обновление\Удаление связанных данных.

        /*
        $model = $this->getRelatedModel();
        $meta = $model->getMeta();
        $foreignField = $meta->getForeignField($this->getTo());
        $qs = $this->getManager()->getQuerySet();

        // If null is allowable, foreign field value should be set to null, otherwise the related objects should be deleted
        if ($foreignField->null) {
            $qs->update([$this->getTo() => null]);
        } else {
            $qs->delete();
        }
        */
    }

    public function getSelectJoin(QueryBuilder $qb, $topAlias)
    {
        // TODO: Implement getSelectJoin() method.
    }

    public function getAttributeName()
    {
        return false;
    }

    public function getValue()
    {
        return $this->getManager();
    }
}

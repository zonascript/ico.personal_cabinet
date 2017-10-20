<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use InvalidArgumentException;
use Xcart\App\Orm\Base;
use Xcart\App\Orm\Exception\OrmExceptions;
use Xcart\App\Orm\ModelInterface;
use Xcart\App\Orm\ManagerInterface;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class ForeignField
 *
 * @package Xcart\App\Orm
 */
class ForeignField extends RelatedField
{
    public $onDelete;

    public $onUpdate;

    public $modelClass;

    public $extra = [];

    public $link;

    public $to;
    public $from;

    public function getOnDelete()
    {
        return $this->onDelete;
    }

    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    public function getForeignPrimaryKey()
    {
        return call_user_func([$this->modelClass, 'getPkName']);
    }

    public function getTo()
    {
        if ($this->to) {
            return $this->to;
        }

        if (count($this->link) == 1) {
            return reset($this->link);
        }

        if (count($this->getRelatedModel()->getPrimaryKeyName(true)) == 1) {
            return $this->getRelatedModel()->getPrimaryKeyName();
        }

        return false;
    }

    public function getFrom()
    {
        if ($this->from) {
            return $this->from;
        }

        if (count($this->link) == 1) {
            $link = array_keys($this->link);
            return reset($link);
        }

        if ($name = $this->getAttributeName()) {
            return $name;
        }

        return false;
    }

    public function getJoin(QueryBuilder $qb, $topAlias)
    {
        $on = [];
        $alias = $qb->makeAliasKey($this->getRelatedModel()->tableName());

        if ($this->link) {
            foreach ($this->link as $from => $to) {
                $on[$topAlias . '.' . $from] = $alias . '.' . $to;
            }
        }
        else {
            if ($to = $this->getTo()) {
                $on = [$topAlias . '.' . $this->getAttributeName() => $alias . '.' . $to];
            }
            else {
                OrmExceptions::FailCreateLink();
            }
        }

        return [
            ['LEFT JOIN', $this->getRelatedTable(), $on, $alias],
        ];
    }

    /**
     * @param $value
     *
     * @return \Xcart\App\Orm\Model|\Xcart\App\Orm\TreeModel|null
     * @throws Exception
     */
    protected function fetch($value)
    {
        if (empty($value)) {
            if ($this->null === true) {
                return null;
            }
            else {
                throw new Exception("Value in fetch method of PrimaryKeyField cannot be empty");
            }
        }

        return $this->fetchModel($value);
    }

    protected function fetchModel($value)
    {
        $filter = [$this->getTo() => $value];

        if ($this->link) {
            $filter = [];

            foreach ($this->link as $from => $to) {
                $filter[$to] = $this->getModel()->getAttribute($from);
            }
        }

        $result = $this->getManager()->cache($this->getModel()->getCache())->get(array_merge($filter, $this->extra));
        $this->getModel()->noCache();

        return $result;
    }

    public function toArray()
    {
        $value = $this->getValue();
        if ($value instanceof ModelInterface) {
            return $value->{$this->getTo()};
        }

        return $value;
    }

    public function getSelectJoin(QueryBuilder $qb, $topAlias)
    {
        // TODO: Implement getSelectJoin() method.
    }

    /**
     * @param $value
     * @param AbstractPlatform $platform
     *
     * @return null|ModelInterface
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof ModelInterface) {
            return $value;
        }
        else if (!is_null($value)) {
            return $this->fetchModel($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @param AbstractPlatform $platform
     *
     * @return null|int
     */
    public function convertToPHPValueSQL($value, AbstractPlatform $platform)
    {
        if ($value instanceof ModelInterface) {
            return $value->{$this->getTo()};
        }

        return $value;
    }

    public function setValue($value)
    {
        if ($value instanceof ModelInterface) {
            $value = $value->{$this->getTo()};
        }
        parent::setValue($value);
    }

    /**
     * @param $value
     * @param AbstractPlatform $platform
     *
     * @return int|string
     */
    public function convertToDatabaseValueSql($value, AbstractPlatform $platform)
    {
        return parent::convertToDatabaseValueSQL($value instanceof ModelInterface ? $value->{$this->getTo()} : $value, $platform);
    }

    /**
     * @return \Xcart\App\Orm\Manager|\Xcart\App\Orm\QuerySet
     */
    public function getManager()
    {
        return call_user_func([$this->modelClass, 'objects']);
    }
}

<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class RelatedField
 * @package Xcart\App\Orm
 */
abstract class RelatedField extends IntField
{
    public $namePostfix = '_id';

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var string sql type of field get type name from const Doctrine\DBAL\Types\Type class
     *             default = 'integer'
     */
    public $sqlType = Type::INTEGER;

    protected $_model;

    protected $_relatedModel;
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @return \Doctrine\DBAL\Types\Type
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSqlType()
    {
        return Type::getType($this->sqlType);
    }

    abstract public function getJoin(QueryBuilder $qb, $topAlias);
    
    abstract public function getSelectJoin(QueryBuilder $qb, $topAlias);

    abstract protected function fetch($value);

    /**
     * @return \Xcart\App\Orm\Model
     */
    public function getRelatedModel()
    {
        if (!$this->_relatedModel) {
            $this->_relatedModel = new $this->modelClass();
        }
        return $this->_relatedModel;
    }

    public function getTable()
    {
        return call_user_func([$this->ownerClassName, 'tableName']);
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Connection $db
     * @return $this
     */
    public function setConnection(Connection $db)
    {
        $this->connection = $db;
        return $this;
    }

    public function getRelatedTable()
    {
        return call_user_func([$this->modelClass, 'tableName']);
    }

    public function buildSelectQuery(QueryBuilder $qb, $topAlias)
    {
        $joinAlias = '???';
        foreach ($this->getSelectJoin($qb, $topAlias) as $join) {
            list($joinType, $tableName, $on, $alias) = $join;

            if ($qb->hasJoin($tableName)) {
                $joinAlias = $qb->getJoinAlias($tableName);
            }
            else {
                $qb->join($joinType, $tableName, $on, $alias);
                $joinAlias = $alias;
            }
        }

        return $joinAlias;
    }

    public function buildQuery(QueryBuilder $qb, $topAlias)
    {
        $joinAlias = '???';
        foreach ($this->getJoin($qb, $topAlias) as $join) {
            list($joinType, $tableName, $on, $alias) = $join;

            if ($qb->hasJoin($tableName)) {
                $joinAlias = $qb->getJoinAlias($tableName);
            }
            else {
                $qb->join($joinType, $tableName, $on, $alias);
                $joinAlias = $alias;
            }
        }
        return $joinAlias;
    }

    public function getAttributeName()
    {
        $name = parent::getAttributeName();

        if ($name == $this->name)
        {
            return $name . $this->namePostfix;
        }

        return $name;
    }

    abstract public function getManager();
}

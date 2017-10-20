<?php

namespace Xcart\App\Orm;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Mindy\QueryBuilder\Expression;
use Mindy\QueryBuilder\Q\QAnd;
use Xcart\App\Orm\Exception\MultipleObjectsReturned;
use Mindy\QueryBuilder\Aggregation\Aggregation;
use Mindy\QueryBuilder\Aggregation\Avg;
use Mindy\QueryBuilder\Aggregation\Count;
use Mindy\QueryBuilder\Aggregation\Max;
use Mindy\QueryBuilder\Aggregation\Min;
use Mindy\QueryBuilder\Aggregation\Sum;
use Mindy\QueryBuilder\Q\QAndNot;
use Mindy\QueryBuilder\Q\QOrNot;
use Mindy\QueryBuilder\QueryBuilder;
use Xcart\App\Orm\Fields\RelatedField;

/**
 * Class QuerySet
 * @package Xcart\App\Orm
 */
class QuerySet extends QuerySetBase
{
    /**
     * @var array a list of relations that this query should be performed with
     */
    protected $with = [];
    protected $_group = [];
    protected $sql;
    protected $cache;

    protected $_data;

    /**
     * Executes query and returns all results as an array.
     * If null, the DB connection returned by [[modelClass]] will be used.
     *
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function all()
    {
        if ($this->_data) {
            $rows = $this->_data;
        }
        else {
            $sql = $this->sql === null ? $this->allSql() : $this->sql;

            $rows = $this->execute($sql);
        }

        if ($this->asArray) {
            return !empty($this->with) ? $this->populateWith($rows) : $rows;
        }

        return $this->createModels($rows);
    }

    /**
     * @param int $batchSize
     *
     * @return \Xcart\App\Orm\BatchDataIterator
     * @throws \Exception
     */
    public function batch($batchSize = 100)
    {
        return new BatchDataIterator($this->getConnection(), [
            'qs' => $this,
            'batchSize' => $batchSize,
            'each' => false,
            'asArray' => $this->asArray,
        ]);
    }

    /**
     * @param int $batchSize
     *
     * @return \Xcart\App\Orm\BatchDataIterator
     * @throws \Exception
     */
    public function each($batchSize = 100)
    {
        return new BatchDataIterator($this->getConnection(), [
            'qs' => $this,
            'batchSize' => $batchSize,
            'each' => true,
            'asArray' => $this->asArray,
        ]);
    }

    /**
     * @param array $columns
     * @param bool $flat
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function valuesList($columns, $flat = false)
    {

        if ($this->_data) {
            $rows = $this->_data;
        }
        else {
            $rows = [];
            $qb = clone $this->getQueryBuilder();

            if ($stmt = $this->getConnection()->query($qb->select($columns)->toSQL())) {
                $rows = $stmt->fetchAll();
            }
        }

        if ($flat) {
            $flatArr = [];
            foreach ($rows as $item) {
                $flatArr = array_merge($flatArr, array_values($item));
            }
            return $flatArr;
        } else {
            return $rows;
        }
    }

    /**
     * Update records
     *
     * @param array $attributes
     *
     * @return int updated records
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function update(array $attributes)
    {
        return $this->getConnection()->executeUpdate($this->updateSql($attributes));
    }

    /**
     * @param array $attributes
     *
     * @return string
     * @throws \Exception
     */
    public function updateSql(array $attributes)
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            $attrs[$this->getModel()->convertToPrimaryKeyName($key)] = $value;
        }
        return $this->getQueryBuilder()->setTypeUpdate()->update($this->getModel()->tableName(), $attrs)->toSQL();
    }

    /**
     * Get model if exists. Else create model without save.
     *
     * @param array $attributes
     *
     * @return array [\Xcart\App\Orm\Model, isNew]
     * @throws
     */
    public function getOrNew(array $attributes)
    {
        $model = $this->get($attributes);
        if ($model === null) {
            $className = get_class($this->getModel());
            /** @var Model $model */
            $model = new $className($attributes);
            return [$model, true];
        }

        return [$model, false];
    }

    /**
     * Get model if exists. Else create model.
     *
     * @param array $attributes
     *
     * @return array[\Xcart\App\Orm\Model, boolean]
     * @throws
     */
    public function getOrCreate(array $attributes)
    {
        list($model, $isNew) = $this->getOrNew($attributes);
        if ($isNew) {
            $model->save();
        }

        return [$model, $isNew];
    }

    /**
     * Find and update model if exists. Else create model.
     *
     * @param array $attributes
     * @param array $updateAttributes
     *
     * @return array [null|\Xcart\App\Orm\ModelInterface|\Xcart\App\Orm\Orm, isNew]
     * @throws
     */
    public function updateOrCreate(array $attributes, array $updateAttributes)
    {
        list($model, $isNew) = $this->getOrNew($attributes);

        $model->setAttributes($updateAttributes);
        $model->save();

        return [$model, $isNew];
    }

    /**
     * Paginate models
     *
     * @param int $page
     * @param int $pageSize
     *
     * @return $this
     * @throws \Exception
     */
    public function paginate($page = 1, $pageSize = 10)
    {
        $this->getQueryBuilder()->paginate($page, $pageSize);
        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function allSql()
    {
        $qb = clone $this->getQueryBuilder();
        return $qb->setTypeSelect()->toSQL();
    }

    /**
     * @param array $filter
     *
     * @return string
     * @throws \Exception
     */
    public function getSql($filter = [])
    {
        if ($filter) {
            $this->filter($filter);
        }
        $qb = clone $this->getQueryBuilder();
        return $qb->setTypeSelect()->toSQL();
    }

    protected function execute($sql, array $params = [], $types = [])
    {
        $qcp = null;
        if ($this->cache && $this->getConnection()->getConfiguration()->getResultCacheImpl()) {
            $qcp = new QueryCacheProfile($this->cache);
        }


        $stmt = $this->getConnection()->executeQuery($sql, $params, $types, $qcp);
        $rows = $stmt->fetchAll()?:[];
        $stmt->closeCursor();

        return $rows;
    }

    /**
     * Executes query and returns a single row of result.
     *
     * @param array $filter
     *
     * @return array|null|\Xcart\App\Orm\ModelInterface
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @throws \Xcart\App\Orm\Exception\MultipleObjectsReturned
     */
    public function get($filter = [])
    {
        $rows = $this->execute($this->getSql($filter));

        if (count($rows) > 1) {
            throw new MultipleObjectsReturned();
        } elseif (count($rows) === 0) {
            return null;
        }

        if (!empty($this->with)) {
            $rows = $this->populateWith($rows);
        }
        $row = array_shift($rows);
        if ($this->asArray) {
            return $row;
        } else {
            $model = $this->createModel($row);
            $model->setIsNewRecord(false);
            return $model;
        }
    }

    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Converts array prefix to string key
     * @param array $prefix
     * @return string
     */
    protected function prefixToKey(array $prefix)
    {
        return implode('__', $prefix);
    }

    public function with($with)
    {
        if (!is_array($with)) {
            $with = [$with];
        }

        foreach ($with as $name => $fields) {
            if (is_numeric($name)) {
                $name = $fields;
            }

            if ($this->getModel()->getMeta()->hasRelatedField($name)) {
                $this->with[] = $name;
                $field = $this->getModel()->getField($name);

                if ($field instanceof RelatedField) {
                    foreach ($field->getJoin($this->getQueryBuilder(), $this->getTableAlias()) as $join) {
                        list($type, $table, $on, $alias) = $join;
                        $this->join($type, $table, $on, $alias);
                    }
                }
            }
        }
        return $this;
    }

    protected function convertQuery($query)
    {
        if (is_array($query)) {
            return array_map(function ($value) {
                if ($value instanceof Model) {
                    return $value->pk;
                }
                else if ($value instanceof Manager || $value instanceof QuerySet) {
                    return $value->getQueryBuilder();
                }
                return $value;
            }, $query);
        } else {
            return $query;
        }
    }

    /**
     * @param array $query
     *
     * @return $this
     * @throws \Exception
     */
    public function filter($query)
    {
        $this->getQueryBuilder()->where($this->convertQuery($query));
        return $this;
    }

    /**
     * @param array $query
     *
     * @return $this
     * @throws \Exception
     */
    public function orFilter(array $query)
    {
        $this->getQueryBuilder()->orWhere($this->convertQuery($query));
        return $this;
    }

    /**
     * @param array $query
     *
     * @return $this
     * @throws \Exception
     */
    public function exclude(array $query)
    {
        $this->getQueryBuilder()->where(new QAndNot($this->convertQuery($query)));
        return $this;
    }

    /**
     * @param array $query
     *
     * @return $this
     * @throws \Exception
     */
    public function orExclude(array $query)
    {
        $this->getQueryBuilder()->orWhere(new QOrNot($this->convertQuery($query)));
        return $this;
    }

    /**
     * Converts name => `name`, user.name => `user`.`name`
     *
     * @param string $name Column name
     *
     * @return string Quoted column name
     * @throws \Exception
     */
    public function quoteColumnName($name)
    {
        return $this->getConnection()->quoteIdentifier($name);
    }

    public function getOrder()
    {
        list($order, $options) = $this->getQueryBuilder()->getOrder();

        return $order;
    }

    /**
     * Order by alias
     *
     * @param $columns
     *
     * @return $this
     * @throws \Exception
     */
    public function order($columns)
    {
        if (is_array($columns)) {
            $newColumns = array_map(function ($value) {

                if ($value instanceof Model) {
                    return $value->pk;
                }
                else if ($value instanceof Manager || $value instanceof QuerySet) {
                    return $value->getQueryBuilder();
                }
//                else if ($value instanceof Expression) {
//                    return $value->toSQL($this->getQueryBuilder());
//                }
                else if (is_string($value)) {
                    $direction = substr($value, 0, 1) === '-' ? '-' : '';

                    if ($direction) {
                        $column = substr($value, 1);
                    }
                    else {
                        $column = $value;
                    }
                    

                    if ($this->getModel()->getMeta()->hasForeignField($column)) {
                        $field = $this->getModel()->getField($column);

                        return $direction . $field->getAttributeName();
                    }
                    else if ($field = $this->getModel()->getField($column)) {
                        return $direction . $field->getAttributeName();
                    }
                    else {
                        return $value;
                    }
                }
                return $value;
            }, $columns);
        } else {
            $newColumns = $columns;
        }
        $this->getQueryBuilder()->order($newColumns);
        return $this;
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function sum($q)
    {
        return $this->aggregate(new Sum($q));
    }

    /**
     * @param string $q
     *
     * @return float|int
     * @throws \Exception
     */
    public function sumSql($q)
    {
        return $this->buildAggregateSql(new Sum($q));
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function average($q)
    {
        return $this->aggregate(new Avg($q));
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Exception
     */
    public function averageSql($q)
    {
        return $this->buildAggregateSql(new Avg($q));
    }

    /**
     * @param $columns
     * @param null $option
     *
     * @return $this
     * @throws \Exception
     */
    public function select($columns, $option = null)
    {
        $this->getQueryBuilder()->select($columns, $option);
        return $this;
    }

    public function addSelect($columns, $option = null)
    {
        $select = $this->getQueryBuilder()->getSelect();
        $this->getQueryBuilder()->select(array_merge($select, $columns), $option);
        return $this;
    }

    private function buildAggregateSql(Aggregation $q)
    {
        $qb = clone $this->getQueryBuilder();
        
//        list($order, $orderOptions) = $qb->getOrder();
        $select = $qb->getSelect();


//        $select = $qb->getQueryBuilder()->getSelect();

        $sql = $qb->order(null)->select(array_merge([$q], $select))->toSQL();
//        $qb->select($select)->order($order, $orderOptions);
        return $sql;
    }

    private function aggregate(Aggregation $q)
    {
        $sql = $this->buildAggregateSql($q);
        $statement = $this->getConnection()->query($sql);
        $value = $statement->fetch();
        if (is_array($value)) {
            $value = end($value);
        }
        return strpos($value, '.') !== false ? floatval($value) : intval($value);
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function min($q)
    {
        return $this->aggregate(new Min($q));
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Exception
     */
    public function minSql($q)
    {
        return $this->buildAggregateSql(new Min($q));
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function max($q)
    {
        return $this->aggregate(new Max($q));
    }

    /**
     * @param null|string|array $q
     *
     * @return float|int
     * @throws \Exception
     */
    public function maxSql($q)
    {
        return $this->buildAggregateSql(new Max($q));
    }

    public function delete()
    {
        $statement = $this->getConnection()->prepare($this->deleteSql());
        return $statement->execute();
    }

    public function deleteSql()
    {
//        if ($this->filterHasJoin()) {
//            $this->prepareConditions();
//            return $this->createCommand()->delete($tableName, [
//                $this->getPrimaryKeyName() => $this->valuesList(['pk'], true)
//            ], $this->params);
//        }

        $builder = $this->getQueryBuilder()
            ->setTypeDelete()
            ->setAlias(null);
        return $builder->toSQL();
    }

    /**
     * @param null|array|string $q
     *
     * @return string
     * @throws \Exception
     */
    public function countSql($q = '*')
    {
        return $this->buildAggregateSql(new Count($q));
    }

    /**
     * @param string $q
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function count($q = '*')
    {
        $clone = clone $this;
        $clone->limit(null);

        if (!empty($this->_group))
        {
            return $clone->getConnection()->executeQuery($clone->buildAggregateSql(new Count($q)))->rowCount();
        }
        else {
            return $clone->aggregate(new Count($q));
        }
    }

    /**
     * Convert array like:
     * >>> ['developer__id' => '1', 'developer__name' = 'Valve']
     * to:
     * >>> ['developer' => ['id' => '1', 'name' => 'Valve']]
     *
     * @param $data
     * @return array
     */
    private function populateWith($data)
    {
        $newData = [];
        foreach ($data as $row) {
            $tmp = [];
            foreach ($row as $key => $value) {
                if (strpos($key, '__') !== false) {
                    list($prefix, $postfix) = explode('__', $key);
                    if (!isset($tmp[$prefix])) {
                        $tmp[$prefix] = [];
                    }
                    $tmp[$prefix][$postfix] = $value;
                } else {
                    $tmp[$key] = $value;
                }
            }
            $newData[] = $tmp;
        }
        return $newData;
    }

    /**
     * Truncate table
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function truncate()
    {
        $connection = $this->getConnection();
        $adapter = QueryBuilder::getInstance($connection)->getAdapter();
        $tableName = $adapter->quoteTableName($adapter->getRawTableName($this->getModel()->tableName()));
        $q = $connection->getDatabasePlatform()->getTruncateTableSQL($tableName);
        return $connection->executeUpdate($q);
    }

    /**
     * @param mixed $fields
     *
     * @return $this
     * @throws \Exception
     */
    public function distinct($fields = true)
    {
        $this->getQueryBuilder()->distinct($fields);
        return $this;
    }

    /**
     * @param $columns
     *
     * @return $this
     * @throws \Exception
     */
    public function group($columns)
    {
        $t_on = [];
        foreach ($columns as $v)
        {
            $t_on[] = $this->fieldAlias($v);
        }

        $this->_group = $t_on;

        $this->getQueryBuilder()->group($this->_group);
        return $this;
    }

    public function addGroup($columns)
    {
        $this->group(array_merge($this->_group, $columns));
        return $this;
    }

    public function having($having)
    {
        if (!empty($having)) {
            $this->getQueryBuilder()->having($having);
        }
        return $this;
    }

    public function limit($limit)
    {
        $this->getQueryBuilder()->limit($limit);
        return $this;
    }

    public function offset($offset)
    {
        $this->getQueryBuilder()->offset($offset);
        return $this;
    }

    public function join($type, $table, $on, $alias)
    {
        $t_on = [];
        foreach ($on as $k=>$v)
        {
            $k = $this->fieldAlias($k);
            $v = $this->fieldAlias($v);

            $t_on[$k] = $v;
        }

        $this->getQueryBuilder()->join($type, $table, $t_on, $alias);
        return $this;
    }

    private function fieldAlias($field)
    {
        if (!is_object($field)
            && !is_numeric($field)
            && strpos($field, '.') === false
            && strpos($field, $this->getQueryBuilder()->getLookupBuilder()->getSeparator()) === false
        ) {
            $field = $this->getTableAlias() .'.'. $field;
        }
        return $field;
    }

    public function cache($life_time = null)
    {
//        $this->cache = ($life_time)?$life_time:$this->getConnection()->getConfiguration()->;
        $this->cache = $life_time;
        return $this;
    }
}

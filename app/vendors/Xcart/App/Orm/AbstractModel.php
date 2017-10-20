<?php
namespace Xcart\App\Orm;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\ManyToManyField;
use Mindy\QueryBuilder\QueryBuilder;
use Xcart\App\Orm\Fields\RelatedField;
use Xcart\App\Orm\Fields\TimestampField;

/**
 * Class NewOrm
 * @package Xcart\App\Orm
 */
class AbstractModel extends Base
{
    /**
     * @return QueryBuilder
     * @throws Exception
     */
    protected function getQueryBuilder()
    {
        return QueryBuilder::getInstance($this->getConnection());
    }

    /**
     * @return \Mindy\QueryBuilder\BaseAdapter|\Mindy\QueryBuilder\Interfaces\ISQLGenerator
     * @throws \Exception
     */
    protected function getAdapter()
    {
        return $this->getQueryBuilder()->getAdapter();
    }

    /**
     * @param array $fields
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    protected function updateInternal(array $fields = [])
    {
        $values = $this->getChangedAttributes($fields);
        if (empty($values)) {
            return true;
        }

        $connection = static::getConnection();
        $adapter = $this->getQueryBuilder()->getAdapter();

        $tableName = $adapter->quoteTableName($adapter->getRawTableName($this->tableName()));
        $rows = $connection->update($tableName, $values, $this->getPrimaryKeyValues(), array_replace($this->extractTypes(array_keys($values)), $this->extractTypes($this->getPrimaryKeyName(true))));

        foreach ($values as $name => $value) {
            $this->setAttribute($name, $value);
        }
        $this->updateRelated();

        return $rows >= 0;
    }

    protected function insertInternal(array $fields = [])
    {
        $dirty = $this->getDirtyAttributes();
        $values = $this->getInsertAttributes($fields);

        if (empty($values)) {
            return true;
        }

        $connection = static::getConnection();
        $adapter = $this->getQueryBuilder()->getAdapter();

        $tableName = $adapter->quoteTableName($adapter->getRawTableName($this->tableName()));
        $inserted = $connection->insert($tableName, $values, $this->extractTypes(array_keys($values)));

        if ($inserted === false) {
            return false;
        }

        foreach (self::getMeta()->getPrimaryKeyName(true) as $primaryKeyName)
        {
            if ($this->getField($primaryKeyName) instanceof AutoField) {
                if (in_array($primaryKeyName, $dirty) === false) {
                    $values[ $primaryKeyName ] = $connection->lastInsertId($this->getSequenceName());
                }
            }
        }

        $this->setAttributes($values);

        return true;
    }

    public function getInsertAttributes(array $fields = [])
    {
        $values = [];
        $platform = $this->getConnection()->getDatabasePlatform();

        if ($fields) {
            foreach ($fields as $field) {
                $values[$field] = $this->getAttribute($field);
            }
            foreach ($this->getPrimaryKeyValues() as $name => $value) {
                if ($value) {
                    $values[$name] = $value;
                }
            }
        }
        else {
            foreach ($this->getAttributes() as $name => $value) {
                if ($value) {
                    $values[$name] = $value;
                }
            }
        }

        /** @var \Xcart\App\Orm\Fields\Field $field */
        foreach (static::getMeta()->getFields() as $name => $field)
        {
            if ($field->getSqlType()
                && !$field->null
                && empty($values[$field->getName()])
                && !(
                    $field instanceof AutoField
                    || $field instanceof RelatedField
                    || $field instanceof TimestampField
                )
            )
            {
                $value = $this->getAttribute($field->getName());
                $values[$field->getName()] = $value === null ? $field->default : $value;
            }
        }

        foreach ($values as $name => $value) {
            if ($this->hasField($name)) {
                $field = $this->getField($name);
                $values[$name] = $field->convertToDatabaseValue($value, $platform);
            }
        }

        return $values;
    }

    protected function extractTypes(array $fields)
    {
        return array_map(function($field){
            return $this->getField($field)->getSqlType()->getBindingType();
        }, $fields);
    }

    /**
     * @return null|string
     * @throws Exception
     */
    public function getSequenceName()
    {
        $schemaManager = $this->getConnection()->getSchemaManager();

        try {
            $schemaManager->listSequences();

            return implode('_', [
                $this->tableName(),
                $this->getPrimaryKeyName(),
                'seq'
            ]);
        } catch (DBALException $e) {
            return null;
        }
    }

    /**
     * @param array $fields
     * @return bool
     * @throws Exception
     */
    public function insert(array $fields = [])
    {
        $connection = $this->getConnection();

        $this->beforeInsertInternal();

        $connection->beginTransaction();
        try {
            if (($inserted = $this->insertInternal($fields))) {
                $connection->commit();
            } else {
                $connection->rollBack();
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->afterInsertInternal();

        if ($inserted) {
            $this->setIsCreated(true);
            $this->setIsNewRecord(false);
            $this->updateRelated();
            $this->attributes->resetOldAttributes();
        }

        return $inserted;
    }

    /**
     * @param array $fields
     * @return bool
     * @throws Exception
     */
    public function update(array $fields = [])
    {
        $connection = $this->getConnection();

        $this->beforeUpdateInternal();

        $connection->beginTransaction();
        try {
            if ($updated = $this->updateInternal($fields)) {
                $connection->commit();
            } else {
                $connection->rollBack();
            }
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->afterUpdateInternal();

        if ($updated) {
            $this->updateRelated();
            $this->attributes->resetOldAttributes();
        }
        return $updated;
    }

    /**
     * @param array $fields
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException|\Exception
     */
    public function getChangedAttributes(array $fields = [])
    {
        $changed = [];

        if (empty($fields)) {
            $fields = $this->getMeta()->getAttributes();
        }

        $dirty = $this->getDirtyAttributes();
        if (empty($dirty)) {
            $dirty = $fields;
        }

        $platform = $this->getConnection()->getDatabasePlatform();

        $meta = self::getMeta();
        foreach ($this->getAttributes() as $name => $attribute) {
            if (in_array($name, $fields) && in_array($name, $dirty) && $meta->hasField($name)) {
                $field = $this->getField($name);

                if ($field->getSqlType() && $attribute != $this->getOldAttribute($name)) {
                    $value = $field->convertToDatabaseValue($attribute, $platform);

                    if ($value != $this->getOldAttribute($name)) {
                        $changed[$name] = $value === null ? $field->convertToDatabaseValue($field->default, $platform) : $value;
//                        $changed[$name] = $value === null ? $field->default : $value;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * @return array|\Doctrine\DBAL\Schema\Table[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Exception
     */
    public static function createSchemaTables()
    {
        $columns = [];
        $indexes = [];

        $meta = self::getMeta();
        $model = self::create();

        $tables = [];
        foreach ($meta->getFields() as $name => $field) {
            $field->setModel($model);

            if ($field instanceof ManyToManyField) {
                /* @var $field \Xcart\App\Orm\Fields\ManyToManyField */
                if ($field->through === null) {
                    $tables[] = new Table($field->getTableName(), $field->getColumns());
                }
            } else {
                $column = $field->getColumn();
                if (empty($column)) {
                    continue;
                }

                $columns[] = $column;
                $indexes = array_merge($indexes, $field->getSqlIndexes());
            }
        }

        $table = new Table($model->tableName(), $columns, $indexes);
        $table->setPrimaryKey($model->getPrimaryKeyName(true), 'primary');

        $tables[] = $table;

        return $tables;
    }
}
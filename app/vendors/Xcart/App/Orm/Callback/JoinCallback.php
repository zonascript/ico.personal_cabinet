<?php

namespace Xcart\App\Orm\Callback;

use Xcart\App\Orm\Fields\ManyToManyField;
use Xcart\App\Orm\Fields\RelatedField;
use Xcart\App\Orm\Model;
use Xcart\App\Orm\ModelInterface;
use Mindy\QueryBuilder\LookupBuilder\LookupBuilder;
use Mindy\QueryBuilder\QueryBuilder;

class JoinCallback
{
    protected $model;

    /**
     * JoinCallback constructor.
     *
     * @param Model|ModelInterface $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function run(QueryBuilder $queryBuilder, LookupBuilder $lookupBuilder, array $lookupNodes)
    {
        $column = '';
        $alias = '';
        /** @var \Xcart\App\Orm\Fields\RelatedField|null $prevField */
        $prevField = null;
        foreach ($lookupNodes as $i => $nodeName) {
            if ($i + 1 == count($lookupNodes)) {
                $column = $nodeName;
            } else {
                if ($nodeName == 'through' && $prevField && $prevField instanceof ManyToManyField) {
                    $alias = $prevField->setConnection($this->model->getConnection())->buildThroughQuery($queryBuilder, $queryBuilder->getAlias());
                }
                else if ($this->model->hasField($nodeName)) {
                    $field = $this->model->getField($nodeName);

                    if ($field instanceof RelatedField) {
                        /** @var \Xcart\App\Orm\Fields\RelatedField $field */
                        $alias = $field->setConnection($this->model->getConnection())->buildQuery($queryBuilder, $queryBuilder->getAlias());
                        $prevField = $field;
                    }
                }
            }
        }

        if (empty($alias) || empty($column)) {
            return false;
        }

        return [$alias, $column];
    }
}
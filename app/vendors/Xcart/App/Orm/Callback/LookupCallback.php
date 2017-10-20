<?php

namespace Xcart\App\Orm\Callback;

use Xcart\App\Orm\Fields\ManyToManyField;
use Xcart\App\Orm\Fields\RelatedField;
use Xcart\App\Orm\Model;
use Xcart\App\Orm\ModelInterface;
use Mindy\QueryBuilder\LookupBuilder\LookupBuilder;
use Mindy\QueryBuilder\QueryBuilder;

class LookupCallback
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * LookupCallback constructor.
     *
     * @param Model|ModelInterface $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function run(QueryBuilder $queryBuilder, LookupBuilder $lookupBuilder, array $lookupNodes, $value)
    {
        $lookup = $lookupBuilder->getDefault();
        $column = '';
        $joinAlias = $queryBuilder->getAlias();

        $ownerModel = $this->model;
        $connection = $ownerModel->getConnection();

        reset($lookupNodes);
        $prevField = $ownerModel->getField(current($lookupNodes));
        if (!$prevField instanceof RelatedField) {
            $prevField = null;
        }

        $prevThrough = false;
        foreach ($lookupNodes as $i => $node) {

            if ($prevField instanceof RelatedField) {
                $relatedModel = $prevField->getRelatedModel();

                if ($node == 'through') {
                    $prevThrough = true;
                }
                else {
                    /** @var \Xcart\App\Orm\Fields\RelatedField $prevField */
                    if ($prevThrough && $prevField instanceof ManyToManyField) {
                        $joinAlias = $prevField
                            ->setConnection($connection)
                            ->buildThroughQuery($queryBuilder, $queryBuilder->getAlias());
                    }
                    else {
                        $joinAlias = $prevField
                            ->setConnection($connection)
                            ->buildQuery($queryBuilder, $queryBuilder->getAlias()); //@TODO: Testings? maybe bug
                    }

                    if (($nextField = $relatedModel->getField($node)) instanceof RelatedField) {
                        $prevField = $nextField;
                    }
                }
            }

            if (count($lookupNodes) == $i + 1) {
                if ($lookupBuilder->hasLookup($node) === false) {
                    $column = $joinAlias . '.' . $lookupBuilder->fetchColumnName($node);
                    $columnWithLookup = $column . $lookupBuilder->getSeparator() . $lookupBuilder->getDefault();
                    $queryBuilder->where([$columnWithLookup => $value]);
                }
                else {
                    $lookup = $node;
                    $column = $joinAlias . '.' . $lookupBuilder->fetchColumnName($lookupNodes[$i - 1]);
                }
            }
        }

        return [$lookup, $column, $value];
    }
}
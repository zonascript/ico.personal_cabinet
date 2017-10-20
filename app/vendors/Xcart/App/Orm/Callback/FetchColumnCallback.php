<?php

namespace Xcart\App\Orm\Callback;

use Xcart\App\Orm\MetaData;
use Xcart\App\Orm\ModelInterface;

class FetchColumnCallback
{
    protected $model;
    protected $meta;

    /**
     * FetchColumnCallback constructor.
     *
     * @param ModelInterface $model
     * @param \Xcart\App\Orm\MetaData $meta
     */
    public function __construct( $model, MetaData $meta)
    {
        $this->model = $model;
        $this->meta = $meta;
    }

    public function run($column)
    {
        if ($column === 'pk') {
            return $this->model->getPrimaryKeyName();
        }
        else if ($this->meta->hasForeignField($column)) {
            return $column;
        }
        else {
            $fields = $this->meta->getManyToManyFields();

            foreach ($fields as $field) {
                if (empty($field->through) === false) {
                    $meta = MetaData::getInstance($field->through);

                    if ($meta->hasForeignField($column)) {
                        return $column;
                    }
                }
            }
            return $column;
        }
        return $column;
    }
}
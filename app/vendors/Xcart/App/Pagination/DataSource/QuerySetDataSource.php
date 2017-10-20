<?php

namespace Xcart\App\Pagination\DataSource;

use Xcart\App\Orm\Manager;
use Xcart\App\Orm\QuerySet;

class QuerySetDataSource implements DataSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTotal($source)
    {
        if ($source instanceof Manager) {
            $source = $source->getQuerySet();
        }
        $clone = clone $source;
        return $clone->count();
    }

    /**
     * {@inheritdoc}
     */
    public function applyLimit($source, $page, $pageSize)
    {
        if ($source instanceof Manager) {
            $source = $source->getQuerySet();
        }
        $clone = clone $source;
        return $clone->paginate($page, $pageSize)->all();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($source)
    {
        return $source instanceof QuerySet || $source instanceof Manager;
    }
}
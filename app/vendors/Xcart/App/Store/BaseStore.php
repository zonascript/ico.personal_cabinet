<?php

namespace Xcart\App\Store;

use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Orm\QuerySet;

abstract class BaseStore
{
    use SmartProperties;

    /**
     * @param array $data
     *
     * @return QuerySet
     */
    abstract public function populate(array $data);

}
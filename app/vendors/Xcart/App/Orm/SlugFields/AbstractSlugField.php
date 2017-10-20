<?php

namespace Xcart\App\Orm\SlugFields;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Traits\SlugifyTrait;

abstract class AbstractSlugField extends CharField
{
    use SlugifyTrait;

    /**
     * @var string
     */
    public $source = 'name';
}

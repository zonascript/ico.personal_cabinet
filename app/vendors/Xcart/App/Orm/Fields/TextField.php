<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Types\Type;

/**
 * Class TextField
 * @package Xcart\App\Orm
 */
class TextField extends Field
{
    /**
     * @return string
     */
    public function getSqlType()
    {
        return Type::getType(Type::TEXT);
    }
}
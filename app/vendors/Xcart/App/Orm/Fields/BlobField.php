<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Types\Type;

/**
 * Class BlobField
 * @package Xcart\App\Orm
 */
class BlobField extends Field
{
    public function getSqlType()
    {
        return Type::getType(Type::BLOB);
    }
}


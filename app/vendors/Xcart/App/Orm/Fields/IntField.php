<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class IntField
 * @package Xcart\App\Orm
 */
class IntField extends Field
{
    /**
     * @var int|string
     */
    public $length = 11;
    /**
     * @var bool
     */
    public $unsigned = false;

    /**
     * @return Type
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSqlType()
    {
        return Type::getType(Type::INTEGER);
    }

    public function getSqlOptions()
    {
        $options = parent::getSqlOptions();
        if ($this->primary) {
            $options['autoincrement'] = true;
        }

        if ($this->unsigned) {
            $options['unsigned'] = $this->unsigned;
        }

        return $options;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        parent::setValue($this->null ? $value : (int)$value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        return (int)parent::convertToPHPValue($value, $platform);
    }
}

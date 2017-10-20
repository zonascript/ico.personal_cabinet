<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Types\Type;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DateTimeField
 * @package Xcart\App\Orm
 */
class DateTimeField extends DateField
{
    /**
     * {@inheritdoc}
     */
    public function getSqlType()
    {
        return Type::getType(Type::DATETIME);
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationConstraints()
    {
        $constraints = [
            new Assert\DateTime()
        ];
        if ($this->isRequired()) {
            $constraints[] = new Assert\NotBlank();
        }

        return $constraints;
    }
}

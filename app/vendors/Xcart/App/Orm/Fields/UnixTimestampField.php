<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Xcart\App\Orm\ModelInterface;

/**
 * Class DateTimeField
 * @package Xcart\App\Orm
 */
class UnixTimestampField extends IntField
{ /**
 * @var bool
 */
    public $autoNowAdd = false;

    /**
     * @var bool
     */
    public $autoNow = false;

    /**
     * {@inheritdoc}
     */
    public function getSqlType()
    {
        return Type::getType(Type::INTEGER);
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationConstraints()
    {
        $constraints = [
//            new Assert\Date()
        ];
        if ($this->isRequired()) {
            $constraints[] = new Assert\NotBlank();
        }

        return $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequired()
    {
        if ($this->autoNow || $this->autoNowAdd) {
            return false;
        }
        return parent::isRequired();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeInsert(ModelInterface $model, $value)
    {
        if (($this->autoNow || $this->autoNowAdd) && $model->getIsNewRecord()) {
            $model->setAttribute($this->getAttributeName(), time());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeUpdate(ModelInterface $model, $value)
    {
        if ($this->autoNow && $model->getIsNewRecord() === false) {
            $model->setAttribute($this->getAttributeName(), time());
        }
    }

    /**
     * {@inheritdoc}
     */
//    public function getValue()
//    {
//        $adapter = QueryBuilder::getInstance($this->getModel()->getConnection())->getAdapter();
//        return $adapter->getDate($this->value);
//    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->getSqlType()->convertToDatabaseValue($value, $platform);
    }
}

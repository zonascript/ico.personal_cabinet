<?php

namespace Xcart\App\Orm\Fields;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Xcart\App\Orm\ModelInterface;

class TimestampField extends IntField
{
    public $autoNow = false;

    public function getValidationConstraints()
    {
        return array_merge(parent::getValidationConstraints(), [
            new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) {
                if (false == preg_match('/^[1-9][0-9]*$/', $value)) {
                    $context->buildViolation('Incorrect value')
                        ->atPath($this->getAttributeName())
                        ->addViolation();
                }
            })
        ]);
    }
//
//
//    /**
//     * {@inheritdoc}
//     */
//    public function beforeInsert(ModelInterface $model, $value)
//    {
//        if (($this->autoNow) || $model->getIsNewRecord()) {
//            $model->setAttribute($this->getAttributeName(), time());
//        }
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function beforeUpdate(ModelInterface $model, $value)
//    {
//        if ($this->autoNow && $model->getIsNewRecord() === false) {
//            $model->setAttribute($this->getAttributeName(), time());
//        }
//    }
}
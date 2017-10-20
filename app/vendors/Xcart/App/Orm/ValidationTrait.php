<?php
namespace Xcart\App\Orm;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class ValidationTrait
 * @package Xcart\App\Orm
 */
trait ValidationTrait
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors = [];

    /**
     * @return array
     */
    protected function getValidationConstraints()
    {
        return [];
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface|\Symfony\Component\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        return Validation::createValidatorBuilder()->getValidator();
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->beforeValidate();
        $errors = $this->getValidator()->validate($this->getValue(), $this->getValidationConstraints());
        $this->setErrors($errors);
        $this->afterValidate();

        return count($errors) === 0;
    }

    public function beforeValidate() {}
    public function afterValidate() {}

    /**
     * @param ConstraintViolationListInterface $errors
     * @return $this
     */
    protected function setErrors(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        $errors = [];
        foreach ($this->errors as $key => $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}
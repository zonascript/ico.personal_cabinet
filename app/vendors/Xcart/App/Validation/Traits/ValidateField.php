<?php

namespace Xcart\App\Validation\Traits;

use Closure;
use Symfony\Component\Validator\Constraint;
use Xcart\App\Form\Fields\Field;
use Xcart\App\Form\Fields\FileField;
use Xcart\App\Form\ModelForm;
use Xcart\App\Validation\Validator;

/**
 * Class ValidateField
 * @package Mindy\Validation
 */
trait ValidateField
{
    /**
     * @var \Xcart\App\Validation\Validator[]
     */
    public $validators = [];
    /**
     * @var array of errors
     */
    private $_errors = [];

    public function clearErrors()
    {
        $this->_errors = [];
    }

    public function getValidators()
    {
        return $this->validators;
    }

    public function isValid()
    {
        $this->clearErrors();

        $value = $this->getValue();
        $validators = $this->getValidators();

        if ($validators) {
            foreach ($validators as $validator) {
                if ($validator instanceof Closure) {
                    /* @var $validator Closure */
                    /* @var $this \Xcart\App\Validation\Interfaces\IValidateObject */
                    $valid = $validator->__invoke($this->getValue());
                    if ($valid !== true) {
                        if (!is_array($valid)) {
                            $valid = [$valid];
                        }

                        $this->addErrors($valid);
                    }
                }
                else if ($validator instanceof Validator) {
                    if ($this instanceof Field && $this->getForm() instanceof ModelForm) {
                        $validator->setModel($this->form->getInstance());
                    }

                    $validator->clearErrors();

                    if ($validator->validate($value) === false) {
                        $this->addErrors($validator->getErrors());
                    }
                }
//                else if ($validator instanceof Constraint) {
//                    /** @var \Xcart\App\Orm\Fields\Field $field */
//                    $field = $this->getForm()->getInstance()->getField($this->name);
//                    $field->setValue($value);
//
//                    if ($field->isValid() === false) {
//                        $this->addErrors($field->getErrors());
//                    }
//                }
            }
        }

        if ($this->getForm() instanceof ModelForm) {
            $instance = $this->getForm()->getInstance();

            if ($instance->hasField($this->name) && $field = $instance->getField($this->name)) {
                /** @var \Xcart\App\Orm\Fields\Field $field */
                $field->setValue($value);

                if ($field->isValid() === false) {
                    $this->addErrors($field->getErrors());
                }
                else {
                    $this->setValue($field->getValue());
                }
            }
        }

        return $this->hasErrors() === false;
    }

    public function getErrors()
    {
        return array_unique($this->_errors);
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function addErrors($errors)
    {
        $this->_errors = array_merge($this->_errors, $errors);
    }

    public function addError($error)
    {
        $this->_errors[] = $error;
    }
}

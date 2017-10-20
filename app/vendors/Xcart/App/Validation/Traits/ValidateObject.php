<?php

namespace Xcart\App\Validation\Traits;

use Xcart\App\Form\ModelForm;

/**
 * Class ValidateObject
 * @package Mindy\Validation
 * @property $this Mindy\Validation\Interfaces\IValidateObject
 */
trait ValidateObject
{
    /**
     * @var array
     */
    public $cleanedData = [];
    /**
     * @var array validation errors (attribute name => array of errors)
     */
    private $_errors = [];
    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValidInternal();
    }

    public function isValidInternal()
    {
        $isModelForm = ($this instanceof ModelForm);

        $this->clearErrors();

        /* @var $field \Xcart\App\Orm\Fields\Field|\Xcart\App\Form\Fields\Field */
        /* @var $this \Xcart\App\Validation\Interfaces\IValidateObject|\Xcart\App\Validation\Traits\ValidateObject */
        $fields = $this->getFieldsInit();

        // Fill cleaned data from form
        foreach ($fields as $name => $field) {
            $this->cleanedData[$name] = $field->getValue();
        }

        if ($isModelForm) {
            /** @var ModelForm $this */
            $model = $this->getInstance();
            $model->setAttributes($this->cleanedData);
        }

        foreach ($fields as $name => $field) {
            // If field is valid, trying to run clean<Fieldname> method in current form
            if ($field->isValid()) {
                if (method_exists($this, 'clean' . ucfirst($name))) {
                    $value = call_user_func([$this, 'clean' . ucfirst($name)], $this->cleanedData[$name]);
                    $this->cleanedData[$name] = $value;
                    $field->setValue($value);
                }
            }


            if ($field->hasErrors() !== false) {
                foreach ($field->getErrors() as $error) {
                    $this->addError($name, $error);
                }
            }

            $this->cleanedData[$name] = $field->getValue();
        }
        return $this->hasErrors() === false;
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ~~~
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ~~~
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        } else {
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
        }
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        $this->clearErrorsInternal($attribute);
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrorsInternal($attribute = null)
    {
        /* @var $field \Xcart\App\Orm\Fields\Field|\Xcart\App\Form\Fields\Field */
        /* @var $this \Xcart\App\Validation\Interfaces\IValidateObject|\Xcart\App\Validation\Traits\ValidateObject */
        if ($attribute === null) {
            foreach ($this->getFieldsInit() as $field) {
                $field->clearErrors();
            }
            $this->_errors = [];
        } else {
            if ($this->hasField($attribute)) {
                $this->getField($attribute)->clearErrors();
            }
            unset($this->_errors[$attribute]);
        }
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error)
    {
        /* @var $this \Xcart\App\Validation\Interfaces\IValidateObject|\Xcart\App\Validation\Traits\ValidateObject */
        $this->_errors[$attribute][] = $error;
        if ($this->hasField($attribute)) {
            $this->getField($attribute)->addError($error);
        }
    }

    public function addErrors($errors)
    {
        $this->_errors = array_merge($this->_errors, $errors);
    }
}

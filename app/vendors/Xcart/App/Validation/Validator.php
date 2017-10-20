<?php

namespace Xcart\App\Validation;

use Xcart\App\Orm\Model;

/**
 * Class Validator
 * @package Mindy\Validation
 */
abstract class Validator
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var Model|string
     */
    protected $model;
    /**
     * @var array
     */
    private $_errors = [];

    /**
     * @param $value
     * @return mixed
     */
    abstract public function validate($value);

    protected function addError($error)
    {
        $this->_errors[] = $error;
    }

    public function clearErrors()
    {
        $this->_errors = [];
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}

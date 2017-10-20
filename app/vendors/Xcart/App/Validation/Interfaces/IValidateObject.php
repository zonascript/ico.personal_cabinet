<?php

namespace Xcart\App\Validation\Interfaces;

/**
 * Interface IValidateObject
 * @package Mindy\Validation
 */
interface IValidateObject
{
    /**
     * @return mixed the initialized fields for validation
     */
    public function getFieldsInit();

    /**
     * @param $attribute string
     * @return bool check the field isset
     */
    public function hasField($attribute);

    /**
     * @param $attribute string
     * @return object field instance
     */
    public function getField($attribute);
}

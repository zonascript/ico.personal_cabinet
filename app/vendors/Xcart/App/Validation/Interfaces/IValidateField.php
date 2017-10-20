<?php

namespace Xcart\App\Validation\Interfaces;

/**
 * Interface IValidateField
 * @package Mindy\Validation
 */
interface IValidateField
{
    /**
     * @return mixed the value for validation
     */
    public function getValue();
}

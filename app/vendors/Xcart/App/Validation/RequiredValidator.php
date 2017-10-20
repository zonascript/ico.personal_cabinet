<?php

namespace Xcart\App\Validation;

use Xcart\App\Translate\Translate;

/**
 * Class RequiredValidator
 * @package Mindy\Validation
 */
class RequiredValidator extends Validator
{
    /**
     * @var string
     */
    public $message = "Cannot be empty";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function validate($value)
    {
        if (is_null($value) || $value === "" || (is_array($value) && $value === [])) {
            $this->addError(Translate::getInstance()->t('validation', $this->message, [
                '{name}' => $this->getName()
            ]));
        }

        return $this->hasErrors() === false;
    }
}

